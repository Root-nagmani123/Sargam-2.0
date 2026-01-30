@extends('admin.layouts.master')
@section('title', 'Edit ID Card Request - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid idcard-create-page">
    <!-- Breadcrumb + Page Title -->
<x-breadcrum title="Edit ID Card Request"></x-breadcrum>
<div class="card idcard-create-type-card mb-4">
    <div class="card-body py-3 px-4">
        <div class="d-flex flex-wrap gap-4 align-items-center">
            <div class="form-check idcard-radio-option mb-0">
                        <input class="form-check-input" type="radio" name="employee_type" id="permanent" value="Permanent Employee" 
                               {{ old('employee_type', $request->employee_type) == 'Permanent Employee' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="permanent">Permanent Employee</label>
                    </div>
                    <div class="form-check idcard-radio-option mb-0">
                        <input class="form-check-input" type="radio" name="employee_type" id="contractual" value="Contractual Employee"
                               {{ old('employee_type', $request->employee_type) == 'Contractual Employee' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="contractual">Contractual Employee</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Form Card -->
        <div class="card idcard-create-form-card mb-4">
            <div class="card-body p-4">
                <h6 class="idcard-form-title mb-4">Please add the Request For Employee ID Card</h6>
                <div class="row g-3">
                    <!-- Row 1: Card Type, Sub Type, Request For -->
                    <div class="col-md-4">
                        <label for="card_type" class="form-label">Card Type <span class="text-danger">*</span></label>
                        <select name="card_type" id="card_type" class="form-select @error('card_type') is-invalid @enderror" required>
                            <option value="">Select Card Type</option>
                            <option value="LBSNAA" {{ old('card_type', $request->card_type) == 'LBSNAA' ? 'selected' : '' }}>LBSNAA</option>
                            <option value="Visitor" {{ old('card_type', $request->card_type) == 'Visitor' ? 'selected' : '' }}>Visitor</option>
                            <option value="Contractor" {{ old('card_type', $request->card_type) == 'Contractor' ? 'selected' : '' }}>Contractor</option>
                        </select>
                        @error('card_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="sub_type" class="form-label">Sub Type <span class="text-danger">*</span></label>
                        <select name="sub_type" id="sub_type" class="form-select @error('sub_type') is-invalid @enderror" required>
                            <option value="">Select Sub Type</option>
                            <option value="Gazetted A Staff" {{ old('sub_type', $request->sub_type) == 'Gazetted A Staff' ? 'selected' : '' }}>Gazetted A Staff</option>
                            <option value="Non-Gazetted" {{ old('sub_type', $request->sub_type) == 'Non-Gazetted' ? 'selected' : '' }}>Non-Gazetted</option>
                            <option value="Support Staff" {{ old('sub_type', $request->sub_type) == 'Support Staff' ? 'selected' : '' }}>Support Staff</option>
                        </select>
                        @error('sub_type')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label for="request_for" class="form-label">Request For <span class="text-danger">*</span></label>
                        <select name="request_for" id="request_for" class="form-select @error('request_for') is-invalid @enderror" required>
                            <option value="">Select Request</option>
                            <option value="Own ID Card" {{ old('request_for', $request->request_for) == 'Own ID Card' ? 'selected' : '' }}>Own ID Card</option>
                            <option value="Family ID Card" {{ old('request_for', $request->request_for) == 'Family ID Card' ? 'selected' : '' }}>Family ID Card</option>
                            <option value="Replacement" {{ old('request_for', $request->request_for) == 'Replacement' ? 'selected' : '' }}>Replacement</option>
                        </select>
                        @error('request_for')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Row 2: Name, Designation -->
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" 
                               value="{{ old('name', $request->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label">Designation</label>
                        <input type="text" name="designation" id="designation" class="form-control @error('designation') is-invalid @enderror" 
                               value="{{ old('designation', $request->designation) }}">
                        @error('designation')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Row 3: Date of Birth, Father Name -->
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror" 
                               value="{{ old('date_of_birth', $request->date_of_birth?->format('Y-m-d')) }}">
                        @error('date_of_birth')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="father_name" class="form-label">Father Name</label>
                        <input type="text" name="father_name" id="father_name" class="form-control @error('father_name') is-invalid @enderror" 
                               value="{{ old('father_name', $request->father_name) }}">
                        @error('father_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Row 4: Academy Joining, ID Card Valid Upto -->
                    <div class="col-md-6">
                        <label for="academy_joining" class="form-label">Academy Joining</label>
                        <input type="date" name="academy_joining" id="academy_joining" class="form-control @error('academy_joining') is-invalid @enderror" 
                               value="{{ old('academy_joining', $request->academy_joining?->format('Y-m-d')) }}">
                        @error('academy_joining')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="id_card_valid_upto" class="form-label">ID Card Valid Upto</label>
                        <input type="text" name="id_card_valid_upto" id="id_card_valid_upto" class="form-control @error('id_card_valid_upto') is-invalid @enderror" 
                               value="{{ old('id_card_valid_upto', $request->id_card_valid_upto) }}" placeholder="DD/MM/YYYY">
                        @error('id_card_valid_upto')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Row 5: Mobile Number, Telephone Number -->
                    <div class="col-md-6">
                        <label for="mobile_number" class="form-label">Mobile Number</label>
                        <input type="tel" name="mobile_number" id="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror" 
                               value="{{ old('mobile_number', $request->mobile_number) }}" placeholder="10 digit mobile number">
                        @error('mobile_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="telephone_number" class="form-label">Telephone Number</label>
                        <input type="tel" name="telephone_number" id="telephone_number" class="form-control @error('telephone_number') is-invalid @enderror" 
                               value="{{ old('telephone_number', $request->telephone_number) }}">
                        @error('telephone_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Row 6: Section, Approval Authority -->
                    <div class="col-md-6">
                        <label for="section" class="form-label">Section</label>
                        <input type="text" name="section" id="section" class="form-control @error('section') is-invalid @enderror" 
                               value="{{ old('section', $request->section) }}" placeholder="Enter section">
                        @error('section')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="approval_authority" class="form-label">Approval Authority</label>
                        <input type="text" name="approval_authority" id="approval_authority" class="form-control @error('approval_authority') is-invalid @enderror" 
                               value="{{ old('approval_authority', $request->approval_authority) }}" placeholder="Enter approval authority">
                        @error('approval_authority')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Row 7: Vendor/Organization, Blood Group -->
                    <div class="col-md-6">
                        <label for="vendor_organization_name" class="form-label">Vendor / Organization Name</label>
                        <input type="text" name="vendor_organization_name" id="vendor_organization_name" class="form-control @error('vendor_organization_name') is-invalid @enderror" 
                               value="{{ old('vendor_organization_name', $request->vendor_organization_name) }}">
                        @error('vendor_organization_name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="blood_group" class="form-label">Blood Group <span class="text-danger">*</span></label>
                        <select name="blood_group" id="blood_group" class="form-select @error('blood_group') is-invalid @enderror" required>
                            <option value="">Select Blood Group</option>
                            <option value="O+" {{ old('blood_group', $request->blood_group) == 'O+' ? 'selected' : '' }}>O+</option>
                            <option value="O-" {{ old('blood_group', $request->blood_group) == 'O-' ? 'selected' : '' }}>O-</option>
                            <option value="A+" {{ old('blood_group', $request->blood_group) == 'A+' ? 'selected' : '' }}>A+</option>
                            <option value="A-" {{ old('blood_group', $request->blood_group) == 'A-' ? 'selected' : '' }}>A-</option>
                            <option value="B+" {{ old('blood_group', $request->blood_group) == 'B+' ? 'selected' : '' }}>B+</option>
                            <option value="B-" {{ old('blood_group', $request->blood_group) == 'B-' ? 'selected' : '' }}>B-</option>
                            <option value="AB+" {{ old('blood_group', $request->blood_group) == 'AB+' ? 'selected' : '' }}>AB+</option>
                            <option value="AB-" {{ old('blood_group', $request->blood_group) == 'AB-' ? 'selected' : '' }}>AB-</option>
                        </select>
                        @error('blood_group')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Upload Photo & Documents -->
                    <div class="col-md-6">
                        <label class="form-label">Upload Photo</label>
                        @if($request->photo)
                            <div class="alert alert-success py-2 px-3 mb-2 small">
                                <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">check_circle</i>
                                Current photo exists
                            </div>
                        @endif
                        <div class="idcard-upload-zone" id="photoUploadArea">
                            <input type="file" name="photo" id="photo" class="d-none @error('photo') is-invalid @enderror" 
                                   accept="image/*" onchange="displayFileName(this, 'photoName')">
                            <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                            <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                        </div>
                        <small id="photoName" class="d-block mt-2 text-body-secondary"></small>
                        @error('photo')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Upload Documents (if any)</label>
                        @if($request->documents)
                            <div class="alert alert-success py-2 px-3 mb-2 small">
                                <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:16px;">check_circle</i>
                                Documents already uploaded
                            </div>
                        @endif
                        <div class="idcard-upload-zone" id="documentsUploadArea">
                            <input type="file" name="documents" id="documents" class="d-none @error('documents') is-invalid @enderror" 
                                   accept=".pdf,.doc,.docx" onchange="displayFileName(this, 'documentsName')">
                            <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                            <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                        </div>
                        <small id="documentsName" class="d-block mt-2 text-body-secondary"></small>
                        @error('documents')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Status (edit only), Remarks -->
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                            <option value="Pending" {{ old('status', $request->status) == 'Pending' ? 'selected' : '' }}>Pending</option>
                            <option value="Approved" {{ old('status', $request->status) == 'Approved' ? 'selected' : '' }}>Approved</option>
                            <option value="Rejected" {{ old('status', $request->status) == 'Rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="Issued" {{ old('status', $request->status) == 'Issued' ? 'selected' : '' }}>Issued</option>
                        </select>
                        @error('status')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-8">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror" 
                                  rows="3" placeholder="Add any additional remarks...">{{ old('remarks', $request->remarks) }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Required Fields Note -->
                <p class="small text-danger mt-4 mb-0">*Required Fields: All marked fields are mandatory for registration</p>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    <a href="{{ route('admin.employee_idcard.show', $request->id) }}" class="btn btn-outline-primary px-4">
                        Cancel
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        Update
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
/* Edit ID Card - Match create page design */
.idcard-create-page .breadcrumb {
    font-size: 0.8125rem;
    --bs-breadcrumb-divider: ">";
}
.idcard-create-page .breadcrumb-item a:hover {
    color: #004a93 !important;
}
.idcard-back-arrow {
    font-size: 1.5rem !important;
    vertical-align: middle;
}
.idcard-create-type-card {
    border: 1px solid rgba(0, 74, 147, 0.25);
    border-radius: 0.5rem;
    background-color: #f0f6fc;
    box-shadow: none;
}
.idcard-radio-option {
    margin-bottom: 0;
}
.idcard-radio-option .form-check-input {
    width: 1.125rem;
    height: 1.125rem;
    border: 2px solid #adb5bd;
    margin-top: 0.125rem;
}
.idcard-radio-option .form-check-input:checked {
    background-color: #004a93;
    border-color: #004a93;
}
.idcard-radio-option .form-check-label {
    font-size: 0.9375rem;
    margin-left: 0.5rem;
    cursor: pointer;
    color: #212529;
}
.idcard-create-form-card {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background: #fff;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05);
}
.idcard-form-title {
    font-size: 0.9375rem;
    font-weight: 500;
    color: #212529;
}
.idcard-upload-zone {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem 1rem !important;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 140px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.idcard-upload-zone:hover {
    background-color: #eef4fc;
    border-color: #004a93;
}
.idcard-upload-icon {
    font-size: 2.5rem !important;
    color: #6c757d;
}
.idcard-upload-zone:hover .idcard-upload-icon {
    color: #004a93;
}
.idcard-upload-zone p {
    font-size: 0.875rem;
    color: #6c757d;
    margin-bottom: 0;
}
.idcard-create-form-card .form-control,
.idcard-create-form-card .form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    font-size: 0.9375rem;
}
.idcard-create-form-card .form-control:focus,
.idcard-create-form-card .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.15);
}
.idcard-create-form-card .form-label {
    font-size: 0.875rem;
    color: #495057;
    margin-bottom: 0.35rem;
}
.btn-outline-primary {
    border: 1px solid #004a93;
    color: #004a93;
}
.btn-outline-primary:hover {
    background-color: #004a93;
    color: #fff;
}
</style>

<script>
    // Enable Bootstrap validation
    (function() {
        'use strict';
        window.addEventListener('load', function() {
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        }, false);
    })();

    function displayFileName(input, displayElementId) {
        const fileName = input.files[0]?.name || '';
        document.getElementById(displayElementId).textContent = fileName ? '✓ Selected: ' + fileName : '';
    }

    document.getElementById('photoUploadArea')?.addEventListener('click', function() {
        document.getElementById('photo').click();
    });
    document.getElementById('photoUploadArea')?.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('idcard-upload-zone-active');
    });
    document.getElementById('photoUploadArea')?.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('idcard-upload-zone-active');
    });
    document.getElementById('photoUploadArea')?.addEventListener('drop', function(e) {
        e.preventDefault();
        const files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('photo').files = files;
            displayFileName(document.getElementById('photo'), 'photoName');
        }
        this.classList.remove('idcard-upload-zone-active');
    });

    document.getElementById('documentsUploadArea')?.addEventListener('click', function() {
        document.getElementById('documents').click();
    });
    document.getElementById('documentsUploadArea')?.addEventListener('dragover', function(e) {
        e.preventDefault();
        this.classList.add('idcard-upload-zone-active');
    });
    document.getElementById('documentsUploadArea')?.addEventListener('dragleave', function(e) {
        e.preventDefault();
        this.classList.remove('idcard-upload-zone-active');
    });
    document.getElementById('documentsUploadArea')?.addEventListener('drop', function(e) {
        e.preventDefault();
        const files = e.dataTransfer.files;
        if (files.length) {
            document.getElementById('documents').files = files;
            displayFileName(document.getElementById('documents'), 'documentsName');
        }
        this.classList.remove('idcard-upload-zone-active');
    });
</script>
<style>
.idcard-upload-zone-active {
    background-color: #e7f1ff !important;
    border-color: #004a93 !important;
}
.idcard-upload-zone-active .idcard-upload-icon {
    color: #004a93 !important;
}
</style>
@endsection
