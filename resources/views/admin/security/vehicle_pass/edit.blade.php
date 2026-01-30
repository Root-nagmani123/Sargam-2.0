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

            @php
                $editApplicantType = old('applicant_type', $vehiclePass->applicant_type ?? ($vehiclePass->gov_veh == 1 ? 'government_vehicle' : 'others'));
            @endphp

            <form action="{{ route('admin.security.vehicle_pass.update', encrypt($vehiclePass->vehicle_tw_pk)) }}" method="POST" enctype="multipart/form-data" id="vehiclePassForm">
                @csrf

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap gap-4 align-items-center">
                            <span class="fw-semibold text-dark me-2">Applicant type:</span>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_employee" value="employee" {{ $editApplicantType === 'employee' ? 'checked' : '' }}>
                                <label class="form-check-label" for="applicant_type_employee">Employee</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_others" value="others" {{ $editApplicantType === 'others' ? 'checked' : '' }}>
                                <label class="form-check-label" for="applicant_type_others">Others</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="applicant_type" id="applicant_type_government" value="government_vehicle" {{ $editApplicantType === 'government_vehicle' ? 'checked' : '' }}>
                                <label class="form-check-label" for="applicant_type_government">Government Vehicle</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label fw-bold text-primary">Vehicle & Applicant Details</label>
                        <hr class="mt-1">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="employee_id_card" class="form-label">ID Card Number</label>
                            <input type="text" name="employee_id_card" id="employee_id_card" class="form-control"
                                value="{{ old('employee_id_card', $vehiclePass->employee_id_card) }}" placeholder="Enter ID Card Number" maxlength="100">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="applicant_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="applicant_name" id="applicant_name" class="form-control @error('applicant_name') is-invalid @enderror"
                                value="{{ old('applicant_name', $vehiclePass->applicant_name ?? $vehiclePass->employee?->first_name . ' ' . $vehiclePass->employee?->last_name) }}" placeholder="Enter Employee Name">
                            @error('applicant_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="designation" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation" class="form-control"
                                value="{{ old('designation', $vehiclePass->designation ?? $vehiclePass->employee?->designation?->designation_name) }}" placeholder="Employee Designation">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" name="department" id="department" class="form-control"
                                value="{{ old('department', $vehiclePass->department ?? $vehiclePass->employee?->department?->department_name) }}" placeholder="Employee Department">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vehicle_type" class="form-label">Vehicle Type <span class="text-danger">*</span></label>
                            <div class="d-flex gap-2 align-items-start">
                                <select name="vehicle_type" id="vehicle_type" class="form-select flex-grow-1 @error('vehicle_type') is-invalid @enderror" required>
                                    <option value="">Select</option>
                                    @foreach($vehicleTypes as $vt)
                                        <option value="{{ $vt->pk }}" {{ (old('vehicle_type', $vehiclePass->vehicle_type) == $vt->pk) ? 'selected' : '' }}>{{ $vt->vehicle_type }}</option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm flex-shrink-0" id="addVehicleTypeBtn" title="Add new vehicle type">
                                    <i class="material-icons material-symbols-rounded" style="font-size:20px;">add</i>
                                </button>
                            </div>
                            @error('vehicle_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="vehicle_no" class="form-label">Vehicle Number <span class="text-danger">*</span></label>
                            <input type="text" name="vehicle_no" id="vehicle_no" class="form-control @error('vehicle_no') is-invalid @enderror"
                                value="{{ old('vehicle_no', $vehiclePass->vehicle_no) }}" placeholder="Enter Vehicle Number" required maxlength="50">
                            @error('vehicle_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <label for="doc_upload" class="form-label">Ownership Documents</label>
                            @if($vehiclePass->doc_upload)
                                @php $ext = strtolower(pathinfo($vehiclePass->doc_upload, PATHINFO_EXTENSION)); @endphp
                                <div class="vehicle-pass-edit-current mb-2 position-relative d-inline-block">
                                    <div class="vehicle-pass-preview-inner position-relative p-2 rounded border bg-light">
                                        @if(in_array($ext, ['jpg','jpeg','png','gif']))
                                            <img src="{{ Storage::url($vehiclePass->doc_upload) }}" alt="Current document" style="max-height:120px; border-radius:4px; display:block;">
                                        @else
                                            <div class="d-flex flex-column align-items-center justify-content-center p-2">
                                                <i class="material-icons material-symbols-rounded text-muted mb-1">description</i>
                                                <span class="small text-break text-center">{{ basename($vehiclePass->doc_upload) }}</span>
                                            </div>
                                        @endif
                                        <a href="{{ Storage::url($vehiclePass->doc_upload) }}" target="_blank" class="btn btn-sm btn-outline-primary mt-2 w-100">View</a>
                                    </div>
                                </div>
                            @endif
                            <div class="vehicle-pass-upload-zone position-relative" id="editDocUploadZone" style="min-height:120px; border:2px dashed #dee2e6; border-radius:0.5rem; padding:1rem; cursor:pointer; background:#f8f9fa;">
                                <input type="file" name="doc_upload" id="doc_upload" class="d-none" accept=".pdf,.jpg,.jpeg,.png">
                                <div class="vehicle-pass-upload-placeholder text-center" id="editDocPlaceholder">
                                    <i class="material-icons material-symbols-rounded text-muted">upload</i>
                                    <p class="small mb-0 mt-1">Click to upload or drag and drop (optional - replace current)</p>
                                </div>
                                <div class="vehicle-pass-upload-preview d-none" id="editDocPreview">
                                    <div class="vehicle-pass-preview-inner position-relative d-inline-block p-2 rounded border bg-white shadow-sm" style="min-width:200px; min-height:100px;">
                                        <img src="" alt="Preview" class="d-none" id="editDocPreviewImg" style="max-height:120px; border-radius:4px;">
                                        <div class="d-none flex-column align-items-center justify-content-center p-2" id="editDocFileNameWrap">
                                            <i class="material-icons material-symbols-rounded text-muted mb-1">description</i>
                                            <p class="small text-muted mb-0 text-break text-center" id="editDocFileName"></p>
                                        </div>
                                        <button type="button" class="btn btn-danger btn-sm position-absolute rounded-circle shadow border border-2 border-white" id="editDocRemove" style="top:-8px;right:-8px;width:32px;height:32px;padding:0;z-index:10;display:inline-flex;align-items:center;justify-content:center;" aria-label="Remove" title="Remove"><i class="material-icons material-symbols-rounded" style="font-size:20px;">close</i></button>
                                    </div>
                                </div>
                            </div>
                            @error('doc_upload')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
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
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Edit doc upload: click zone, preview, remove
    var editZone = document.getElementById('editDocUploadZone');
    var editInput = document.getElementById('doc_upload');
    var editPlaceholder = document.getElementById('editDocPlaceholder');
    var editPreview = document.getElementById('editDocPreview');
    var editPreviewImg = document.getElementById('editDocPreviewImg');
    var editFileName = document.getElementById('editDocFileName');
    var editRemove = document.getElementById('editDocRemove');
    if (editZone && editInput) {
        editZone.addEventListener('click', function(e) {
            if (!$(e.target).closest('#editDocRemove').length) editInput.click();
        });
        editInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file) return;
            var editFileNameWrap = document.getElementById('editDocFileNameWrap');
            if (file.type && file.type.indexOf('image/') === 0) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    editPreviewImg.src = e.target.result;
                    editPreviewImg.classList.remove('d-none');
                    if (editFileNameWrap) editFileNameWrap.classList.add('d-none');
                    editFileName.textContent = '';
                    editPlaceholder.classList.add('d-none');
                    editPreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                editPreviewImg.classList.add('d-none');
                editPreviewImg.src = '';
                editFileName.textContent = file.name;
                if (editFileNameWrap) { editFileNameWrap.classList.remove('d-none'); editFileNameWrap.classList.add('d-flex'); }
                editPlaceholder.classList.add('d-none');
                editPreview.classList.remove('d-none');
            }
        });
    }
    if (editRemove) {
        editRemove.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (editInput) editInput.value = '';
            if (editPreviewImg) { editPreviewImg.src = ''; editPreviewImg.classList.add('d-none'); }
            if (editFileName) editFileName.textContent = '';
            var editFileNameWrap = document.getElementById('editDocFileNameWrap');
            if (editFileNameWrap) { editFileNameWrap.classList.add('d-none'); editFileNameWrap.classList.remove('d-flex'); }
            if (editPreview) editPreview.classList.add('d-none');
            if (editPlaceholder) editPlaceholder.classList.remove('d-none');
        });
    }

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
            if (addVehicleTypeForm) addVehicleTypeForm.reset();
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
                }).then(function(r) {
                    return r.json().then(function(data) {
                        if (!r.ok) {
                            var msg = data.message || 'Could not add vehicle type.';
                            if (data.errors && data.errors.vehicle_type && data.errors.vehicle_type[0]) {
                                msg = data.errors.vehicle_type[0];
                            }
                            addVehicleTypeError.textContent = msg;
                            addVehicleTypeError.classList.remove('d-none');
                            addVehicleTypeSubmit.disabled = false;
                            return;
                        }
                        if (data.success && data.data) {
                            var opt = document.createElement('option');
                            opt.value = data.data.pk;
                            opt.textContent = data.data.vehicle_type;
                            opt.selected = true;
                            vehicleTypeSelect.appendChild(opt);
                            bootstrap.Modal.getInstance(addVehicleTypeModal).hide();
                        }
                        addVehicleTypeSubmit.disabled = false;
                    });
                }).catch(function() {
                    addVehicleTypeSubmit.disabled = false;
                    addVehicleTypeError.textContent = 'Request failed. Please try again.';
                    addVehicleTypeError.classList.remove('d-none');
                });
            });
        }
    }

    // Validate valid_to date is after valid_from
    $('#vech_card_valid_to').on('change', function() {
        var validFrom = new Date($('#veh_card_valid_from').val());
        var validTo = new Date($(this).val());
        if (validTo < validFrom) {
            if (typeof toastr !== 'undefined') toastr.error('Valid To date must be after or equal to Valid From date');
            $(this).val('');
        }
    });

    $('#vehiclePassForm').on('submit', function(e) {
        var validFrom = new Date($('#veh_card_valid_from').val());
        var validTo = new Date($('#vech_card_valid_to').val());
        if (validTo < validFrom) {
            e.preventDefault();
            if (typeof toastr !== 'undefined') toastr.error('Valid To date must be after or equal to Valid From date');
            return false;
        }
    });
});
</script>
@endpush
