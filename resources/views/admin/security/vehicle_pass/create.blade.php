@extends('admin.layouts.master')
@section('title', 'Generate New Vehicle Card Pass - Sargam')
@section('setup_content')
<div class="container-fluid vehicle-pass-create-page">
    <x-breadcrum title="Generate New Vehicle Card Pass"></x-breadcrum>

    <form action="{{ route('admin.security.vehicle_pass.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" id="vehiclePassForm" novalidate>
        @csrf

        @php
            $oldApplicantType = old('applicant_type', 'others');
            $oldIdCard = old('employee_id_card', '');
            $oldName = old('applicant_name', '');
            $oldDesignation = old('designation', '');
            $oldDepartment = old('department', '');
            $oldVehicleNo = old('vehicle_no', '');
            $oldValidFrom = old('veh_card_valid_from', '2026-01-01');
            $oldValidTo = old('vech_card_valid_to', '2027-01-01');
            if (in_array($oldApplicantType, ['employee', 'government_vehicle']) && isset($currentUserEmployee) && $currentUserEmployee) {
                if ($oldIdCard === '') $oldIdCard = $currentUserEmployee->emp_id ?? '';
                if ($oldName === '') $oldName = $currentUserEmployee->name ?? '';
                if ($oldDesignation === '') $oldDesignation = $currentUserEmployee->designation ?? '';
                if ($oldDepartment === '') $oldDepartment = $currentUserEmployee->department ?? '';
            }
        @endphp

        {{-- 3 Radio buttons: Employee, Others, Government Vehicle --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <span class="fw-semibold text-dark me-2">Applicant type:</span>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_employee" value="employee" {{ $oldApplicantType === 'employee' ? 'checked' : '' }}>
                        <label class="form-check-label" for="applicant_type_employee">Employee</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_others" value="others" {{ $oldApplicantType === 'others' ? 'checked' : '' }}>
                        <label class="form-check-label" for="applicant_type_others">Others</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_government" value="government_vehicle" {{ $oldApplicantType === 'government_vehicle' ? 'checked' : '' }}>
                        <label class="form-check-label" for="applicant_type_government">Government Vehicle</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Please enter new configuration for vehicle</h6>

                {{-- Employee / Government Vehicle: logged-in user's details auto-fill; no employee selection. Others: user fills manually. --}}
                <input type="hidden" name="emp_master_pk" id="emp_master_pk" value="{{ in_array($oldApplicantType, ['employee', 'government_vehicle']) && isset($currentUserEmployee) && $currentUserEmployee ? $currentUserEmployee->pk : old('emp_master_pk', '') }}">

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="employee_id_card" class="form-label">ID Card Number <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id_card" id="employee_id_card" class="form-control" value="{{ $oldIdCard }}" placeholder="Enter ID Card Number">
                        @error('employee_id_card')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="applicant_name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="applicant_name" id="applicant_name" class="form-control" value="{{ $oldName }}" placeholder="Enter Employee Name">
                        @error('applicant_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" id="designation" class="form-control" value="{{ $oldDesignation }}" placeholder="Employee Designation">
                        @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                        <input type="text" name="department" id="department" class="form-control" value="{{ $oldDepartment }}" placeholder="Employee Department">
                        @error('department')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                        <div class="d-flex gap-2 align-items-start">
                            <select name="vehicle_type" id="vehicle_type" class="form-select flex-grow-1" required>
                                <option value="">Select</option>
                                @foreach($vehicleTypes as $vt)
                                    <option value="{{ $vt->pk }}" {{ old('vehicle_type') == $vt->pk ? 'selected' : '' }}>{{ $vt->vehicle_type }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" id="addVehicleTypeBtn" title="Add new vehicle type">
                                <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
                            </button>
                        </div>
                        @error('vehicle_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vehicle_no" class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                        <input type="text" name="vehicle_no" id="vehicle_no" class="form-control" value="{{ $oldVehicleNo }}" placeholder="Enter Vehicle Number" required>
                        @error('vehicle_no')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="veh_card_valid_from" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="veh_card_valid_from" id="veh_card_valid_from" class="form-control" value="{{ $oldValidFrom }}" required>
                        @error('veh_card_valid_from')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="vech_card_valid_to" class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="vech_card_valid_to" id="vech_card_valid_to" class="form-control" value="{{ $oldValidTo }}" required>
                        @error('vech_card_valid_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ownership Documents <span class="text-danger">*</span></label>
                        <div class="vehicle-pass-upload-zone position-relative" id="ownershipDocUploadZone">
                            <input type="file" name="doc_upload" id="doc_upload" class="d-none" accept="image/*,.pdf">
                            <div class="vehicle-pass-upload-placeholder" id="ownershipDocPlaceholder">
                                <i class="material-icons material-symbols-rounded vehicle-pass-upload-icon">upload</i>
                                <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                <span class="small text-muted">Allowed: PDF, JPG, PNG. Max size: 2 MB</span>
                            </div>
                            <div class="vehicle-pass-upload-preview d-none" id="ownershipDocPreview">
                                <div class="vehicle-pass-preview-inner position-relative">
                                    <img src="" alt="Document Preview" class="vehicle-pass-preview-img d-none" id="ownershipDocPreviewImg">
                                    <div class="vehicle-pass-preview-filename d-none" id="ownershipDocFileNameWrap">
                                        <i class="material-icons material-symbols-rounded text-muted mb-2">description</i>
                                        <p class="small text-muted mb-0 text-break" id="ownershipDocFileName"></p>
                                    </div>
                                    <button type="button" class="btn btn-danger btn-sm vehicle-pass-preview-remove rounded-circle shadow" id="ownershipDocRemove" aria-label="Remove document" title="Remove">
                                        <i class="material-icons material-symbols-rounded">close</i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @error('doc_upload')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <p class="small text-danger mb-0">*Required Fields: All marked fields are mandatory for registration.</p>

                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    <a href="{{ route('admin.security.vehicle_pass.index') }}" class="btn btn-outline-primary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Modal: Add new vehicle type --}}
<div class="modal fade" id="addVehicleTypeModal" tabindex="-1" aria-labelledby="addVehicleTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVehicleTypeModalLabel">Add New Vehicle Type</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="addVehicleTypeError" class="alert alert-danger d-none" role="alert"></div>
                <form id="addVehicleTypeForm">
                    @csrf
                    <div class="mb-3">
                        <label for="new_vehicle_type_name" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_vehicle_type_name" name="vehicle_type" placeholder="e.g. Car, Two Wheeler" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_vehicle_type_description" class="form-label">Description (optional)</label>
                        <textarea class="form-control" id="new_vehicle_type_description" name="description" rows="2" placeholder="Optional description"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addVehicleTypeSubmit">Save</button>
            </div>
        </div>
    </div>
</div>

<style>
.vehicle-pass-create-page .form-control,
.vehicle-pass-create-page .form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    font-size: 0.9375rem;
}
.vehicle-pass-create-page .form-control:focus,
.vehicle-pass-create-page .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.15);
}
.vehicle-pass-upload-zone {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.vehicle-pass-upload-zone:hover {
    background-color: #eef4fc;
    border-color: #004a93;
}
.vehicle-pass-upload-icon { font-size: 2.5rem !important; color: #6c757d; }
.vehicle-pass-upload-zone:hover .vehicle-pass-upload-icon { color: #004a93; }
.vehicle-pass-upload-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 120px; }
.vehicle-pass-upload-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 160px;
    padding: 1rem;
    width: 100%;
}
.vehicle-pass-preview-inner {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem;
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    min-width: 200px;
    min-height: 140px;
}
.vehicle-pass-preview-img {
    max-width: 100%;
    max-height: 180px;
    object-fit: contain;
    border-radius: 0.375rem;
    display: block;
}
.vehicle-pass-preview-filename {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem;
    text-align: center;
}
.vehicle-pass-preview-filename .material-icons { font-size: 2.5rem !important; }
.vehicle-pass-preview-remove {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 50%;
    border: 2px solid #fff;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
.vehicle-pass-preview-remove .material-icons { font-size: 20px !important; }
.vehicle-pass-preview-remove:hover { background-color: #bb2d3b !important; border-color: #bb2d3b; color: #fff; }
.text-break { word-break: break-all; }
.btn-outline-primary { border: 1px solid #004a93; color: #004a93; }
.btn-outline-primary:hover { background-color: #004a93; color: #fff; }
</style>

@php
    $empDataForJs = [];
    foreach ($employees ?? [] as $e) {
        $name = trim(($e->first_name ?? '') . ' ' . ($e->last_name ?? ''));
        $des = (isset($e->designation) && $e->designation) ? ($e->designation->designation_name ?? '') : '';
        $dept = (isset($e->department) && $e->department) ? ($e->department->department_name ?? '') : '';
        $empDataForJs[(string) $e->pk] = ['name' => $name, 'designation' => $des, 'department' => $dept, 'emp_id' => $e->emp_id ?? ''];
    }
@endphp
@push('scripts')
<script>
(function() {
    'use strict';
    var zone = document.getElementById('ownershipDocUploadZone');
    var input = document.getElementById('doc_upload');
    var placeholder = document.getElementById('ownershipDocPlaceholder');
    var preview = document.getElementById('ownershipDocPreview');
    var previewImg = document.getElementById('ownershipDocPreviewImg');
    var removeBtn = document.getElementById('ownershipDocRemove');

    var fileNameWrap = document.getElementById('ownershipDocFileNameWrap');
    function showPreview(file) {
        if (!file) return;
        if (file.type && file.type.indexOf('image/') === 0) {
            var reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewImg.classList.remove('d-none');
                if (fileNameWrap) fileNameWrap.classList.add('d-none');
                var fn = document.getElementById('ownershipDocFileName');
                if (fn) fn.textContent = '';
                placeholder.classList.add('d-none');
                preview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            previewImg.classList.add('d-none');
            previewImg.src = '';
            var fn = document.getElementById('ownershipDocFileName');
            if (fn) { fn.textContent = file.name; }
            if (fileNameWrap) fileNameWrap.classList.remove('d-none');
            placeholder.classList.add('d-none');
            preview.classList.remove('d-none');
        }
    }

    if (zone) {
        zone.addEventListener('click', function(e) {
            if (!e.target.closest('.vehicle-pass-preview-remove')) input.click();
        });
        zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('border-primary'); });
        zone.addEventListener('dragleave', function() { zone.classList.remove('border-primary'); });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            zone.classList.remove('border-primary');
            var files = e.dataTransfer.files;
            if (files.length) { input.files = files; showPreview(files[0]); }
        });
    }
    if (input) {
        input.addEventListener('change', function() {
            var file = this.files[0];
            if (file) showPreview(file);
        });
    }
    if (removeBtn) {
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            input.value = '';
            previewImg.src = '';
            previewImg.classList.add('d-none');
            var fn = document.getElementById('ownershipDocFileName');
            if (fn) fn.textContent = '';
            if (fileNameWrap) fileNameWrap.classList.add('d-none');
            preview.classList.add('d-none');
            placeholder.classList.remove('d-none');
        });
    }

    // Employee / Government Vehicle: auto-fill from logged-in user only (no employee dropdown). Others: user fills manually.
    var applicantTypeEmployee = document.getElementById('applicant_type_employee');
    var applicantTypeOthers = document.getElementById('applicant_type_others');
    var applicantTypeGovernment = document.getElementById('applicant_type_government');
    var empMasterPkInput = document.getElementById('emp_master_pk');
    var currentUserEmployee = @json($currentUserEmployee ?? null);

    function isEmployeeOrGovVehicle() {
        return (applicantTypeEmployee && applicantTypeEmployee.checked) || (applicantTypeGovernment && applicantTypeGovernment.checked);
    }

    function setApplicantFields(idCard, name, designation, department, readonly) {
        var idCardEl = document.getElementById('employee_id_card');
        var nameEl = document.getElementById('applicant_name');
        var desEl = document.getElementById('designation');
        var deptEl = document.getElementById('department');
        if (idCardEl) { idCardEl.value = idCard || ''; idCardEl.readOnly = !!readonly; }
        if (nameEl) { nameEl.value = name || ''; nameEl.readOnly = !!readonly; }
        if (desEl) { desEl.value = designation || ''; desEl.readOnly = !!readonly; }
        if (deptEl) { deptEl.value = department || ''; deptEl.readOnly = !!readonly; }
    }

    function updateApplicantTypeFields() {
        if (isEmployeeOrGovVehicle()) {
            if (currentUserEmployee && empMasterPkInput) {
                empMasterPkInput.value = currentUserEmployee.pk;
                setApplicantFields(
                    currentUserEmployee.emp_id,
                    currentUserEmployee.name,
                    currentUserEmployee.designation,
                    currentUserEmployee.department,
                    true
                );
            } else if (empMasterPkInput) {
                empMasterPkInput.value = '';
                setApplicantFields('', '', '', '', false);
            }
        } else {
            if (empMasterPkInput) empMasterPkInput.value = '';
            setApplicantFields('', '', '', '', false);
        }
    }

    if (applicantTypeEmployee) applicantTypeEmployee.addEventListener('change', updateApplicantTypeFields);
    if (applicantTypeOthers) applicantTypeOthers.addEventListener('change', updateApplicantTypeFields);
    if (applicantTypeGovernment) applicantTypeGovernment.addEventListener('change', updateApplicantTypeFields);
    updateApplicantTypeFields();

    // Add new vehicle type (modal + AJAX)
    var addVehicleTypeBtn = document.getElementById('addVehicleTypeBtn');
    var addVehicleTypeModal = document.getElementById('addVehicleTypeModal');
    var addVehicleTypeForm = document.getElementById('addVehicleTypeForm');
    var addVehicleTypeSubmit = document.getElementById('addVehicleTypeSubmit');
    var addVehicleTypeError = document.getElementById('addVehicleTypeError');
    var vehicleTypeSelect = document.getElementById('vehicle_type');
    if (addVehicleTypeBtn && addVehicleTypeModal && vehicleTypeSelect) {
        addVehicleTypeBtn.addEventListener('click', function() {
            addVehicleTypeError.classList.add('d-none');
            addVehicleTypeError.textContent = '';
            addVehicleTypeForm.reset();
            var modal = new bootstrap.Modal(addVehicleTypeModal);
            modal.show();
        });
        if (addVehicleTypeSubmit) {
            addVehicleTypeSubmit.addEventListener('click', function() {
                var nameInput = document.getElementById('new_vehicle_type_name');
                var descInput = document.getElementById('new_vehicle_type_description');
                if (!nameInput || !nameInput.value.trim()) {
                    addVehicleTypeError.textContent = 'Vehicle type name is required.';
                    addVehicleTypeError.classList.remove('d-none');
                    return;
                }
                addVehicleTypeError.classList.add('d-none');
                addVehicleTypeSubmit.disabled = true;
                var formData = new FormData();
                formData.append('_token', document.querySelector('input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '');
                formData.append('vehicle_type', nameInput.value.trim());
                formData.append('description', descInput ? descInput.value.trim() : '');
                fetch('{{ route("admin.security.vehicle_type.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function(r) { return r.json(); }).then(function(data) {
                    addVehicleTypeSubmit.disabled = false;
                    if (data.success && data.data) {
                        var opt = document.createElement('option');
                        opt.value = data.data.pk;
                        opt.textContent = data.data.vehicle_type;
                        opt.selected = true;
                        vehicleTypeSelect.appendChild(opt);
                        bootstrap.Modal.getInstance(addVehicleTypeModal).hide();
                    } else {
                        addVehicleTypeError.textContent = data.message || 'Could not add vehicle type.';
                        addVehicleTypeError.classList.remove('d-none');
                    }
                }).catch(function(err) {
                    addVehicleTypeSubmit.disabled = false;
                    addVehicleTypeError.textContent = 'Request failed. Please try again.';
                    addVehicleTypeError.classList.remove('d-none');
                });
            });
        }
    }
})();
</script>
@endpush
@endsection
