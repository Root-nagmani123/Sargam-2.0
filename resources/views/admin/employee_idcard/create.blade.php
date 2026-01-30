@extends('admin.layouts.master')
@section('title', 'Generate New ID Card - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid idcard-create-page">
<x-breadcrum title="Generate New ID Card"></x-breadcrum>

    <form action="{{ route('admin.employee_idcard.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" id="idcardForm" novalidate>
        @csrf

        <!-- Employee Type Selection Card -->
        <div class="card idcard-create-type-card mb-4">
            <div class="card-body py-3 px-4">
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div class="form-check idcard-radio-option mb-0">
                        <input class="form-check-input" type="radio" name="employee_type" id="permanent" value="Permanent Employee"
                               {{ old('employee_type', 'Permanent Employee') == 'Permanent Employee' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="permanent">Permanent Employee</label>
                    </div>
                    <div class="form-check idcard-radio-option mb-0">
                        <input class="form-check-input" type="radio" name="employee_type" id="contractual" value="Contractual Employee"
                               {{ old('employee_type') == 'Contractual Employee' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="contractual">Contractual Employee</label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Request Form Card -->
        <div class="card idcard-create-form-card mb-4">
            <div class="card-body p-4">
                <h6 class="idcard-form-title mb-4">Please add the Request For Employee ID Card</h6>

                @php
                    $oldCardType = old('card_type', 'LBSNAA');
                    $oldSubType = old('sub_type', 'Gazetted A Staff');
                    $oldRequestFor = old('request_for', 'Own ID Card');
                    $oldName = old('name', 'Sargam Admin');
                    $oldDesignation = old('designation', 'Administrative Officer');
                    $oldDob = old('date_of_birth', '1983-10-18');
                    $oldAcademy = old('academy_joining', '2013-09-05');
                    $oldIdValid = old('id_card_valid_upto', '01/01/2027');
                    $oldMobile = old('mobile_number', '9356753250');
                    $oldBlood = old('blood_group', '');
                @endphp

                <!-- ========== PERMANENT EMPLOYEE VIEW ========== -->
                <div id="permanent-view" class="idcard-view-fields">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="card_type_perm" class="form-label">Card Type <span class="text-danger">*</span></label>
                            <select name="card_type" id="card_type_perm" class="form-select idcard-perm-field" data-field="card_type" required>
                                <option value="">Select Card Type</option>
                                <option value="LBSNAA" {{ $oldCardType == 'LBSNAA' ? 'selected' : '' }}>LBSNAA</option>
                                <option value="Visitor" {{ $oldCardType == 'Visitor' ? 'selected' : '' }}>Visitor</option>
                                <option value="Contractor" {{ $oldCardType == 'Contractor' ? 'selected' : '' }}>Contractor</option>
                            </select>
                            @error('card_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="sub_type_perm" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <select name="sub_type" id="sub_type_perm" class="form-select idcard-perm-field" data-field="sub_type" required>
                                <option value="">Select Sub Type</option>
                                <option value="Gazetted A Staff" {{ $oldSubType == 'Gazetted A Staff' ? 'selected' : '' }}>Gazetted-A Staff</option>
                                <option value="Non-Gazetted" {{ $oldSubType == 'Non-Gazetted' ? 'selected' : '' }}>Non-Gazetted</option>
                                <option value="Support Staff" {{ $oldSubType == 'Support Staff' ? 'selected' : '' }}>Support Staff</option>
                            </select>
                            @error('sub_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="request_for_perm" class="form-label">Request For <span class="text-danger">*</span></label>
                            <select name="request_for" id="request_for_perm" class="form-select idcard-perm-field" data-field="request_for" required>
                                <option value="">Select Request</option>
                                <option value="Own ID Card" {{ $oldRequestFor == 'Own ID Card' ? 'selected' : '' }}>Own ID Card</option>
                                <option value="Family ID Card" {{ $oldRequestFor == 'Family ID Card' ? 'selected' : '' }}>Family ID Card</option>
                                <option value="Replacement" {{ $oldRequestFor == 'Replacement' ? 'selected' : '' }}>Replacement</option>
                            </select>
                            @error('request_for')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="name_perm" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name_perm" class="form-control idcard-perm-field" data-field="name" value="{{ $oldName }}" required>
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="designation_perm" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation_perm" class="form-control idcard-perm-field" data-field="designation" value="{{ $oldDesignation }}">
                            @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="date_of_birth_perm" class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth_perm" class="form-control idcard-perm-field" data-field="date_of_birth" value="{{ $oldDob }}">
                            @error('date_of_birth')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="academy_joining_perm" class="form-label">Academy Joining</label>
                            <input type="date" name="academy_joining" id="academy_joining_perm" class="form-control idcard-perm-field" data-field="academy_joining" value="{{ $oldAcademy }}">
                            @error('academy_joining')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mobile_number_perm" class="form-label">Mobile Number</label>
                            <input type="tel" name="mobile_number" id="mobile_number_perm" class="form-control idcard-perm-field" data-field="mobile_number" value="{{ $oldMobile }}" placeholder="10 digit mobile number">
                            @error('mobile_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="telephone_number_perm" class="form-label">Telephone Number</label>
                            <input type="tel" name="telephone_number" id="telephone_number_perm" class="form-control idcard-perm-field" data-field="telephone_number" value="{{ old('telephone_number', '9356753250') }}">
                            @error('telephone_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="blood_group_perm" class="form-label">Blood Group <span class="text-danger">*</span></label>
                            <select name="blood_group" id="blood_group_perm" class="form-select idcard-perm-field" data-field="blood_group" required>
                                <option value="">Select Blood Group</option>
                                <option value="O+ve" {{ $oldBlood == 'O+ve' ? 'selected' : '' }}>O+ve</option>
                                <option value="O+" {{ $oldBlood == 'O+' ? 'selected' : '' }}>O+</option>
                                <option value="O-" {{ $oldBlood == 'O-' ? 'selected' : '' }}>O-</option>
                                <option value="A+" {{ $oldBlood == 'A+' ? 'selected' : '' }}>A+</option>
                                <option value="A-" {{ $oldBlood == 'A-' ? 'selected' : '' }}>A-</option>
                                <option value="B+" {{ $oldBlood == 'B+' ? 'selected' : '' }}>B+</option>
                                <option value="B-" {{ $oldBlood == 'B-' ? 'selected' : '' }}>B-</option>
                                <option value="AB+" {{ $oldBlood == 'AB+' ? 'selected' : '' }}>AB+</option>
                                <option value="AB-" {{ $oldBlood == 'AB-' ? 'selected' : '' }}>AB-</option>
                            </select>
                            @error('blood_group')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="id_card_valid_upto_perm" class="form-label">ID Card Valid Upto</label>
                            <input type="text" name="id_card_valid_upto" id="id_card_valid_upto_perm" class="form-control idcard-perm-field" data-field="id_card_valid_upto" value="{{ $oldIdValid }}" placeholder="DD/MM/YYYY">
                            @error('id_card_valid_upto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                            <div class="idcard-upload-zone position-relative" id="photoUploadAreaPerm">
                                <input type="file" name="photo" id="photo_perm" class="d-none idcard-perm-field" data-field="photo" accept="image/*" required>
                                <div class="idcard-upload-placeholder" id="photoPlaceholderPerm">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                </div>
                                <div class="idcard-upload-preview d-none" id="photoPreviewPerm">
                                    <img src="" alt="Preview" class="idcard-preview-img" id="photoPreviewImgPerm">
                                    <button type="button" class="idcard-preview-remove btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="photoRemovePerm" aria-label="Remove photo">&times;</button>
                                </div>
                            </div>
                            @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- ========== CONTRACTUAL EMPLOYEE VIEW ========== -->
                <div id="contractual-view" class="idcard-view-fields" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="card_type_cont" class="form-label">Card Type <span class="text-danger">*</span></label>
                            <select name="card_type" id="card_type_cont" class="form-select idcard-cont-field" required disabled>
                                <option value="">Select Card Type</option>
                                <option value="LBSNAA" {{ $oldCardType == 'LBSNAA' ? 'selected' : '' }}>LBSNAA</option>
                                <option value="Visitor" {{ $oldCardType == 'Visitor' ? 'selected' : '' }}>Visitor</option>
                                <option value="Contractor" {{ $oldCardType == 'Contractor' ? 'selected' : '' }}>Contractor</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="sub_type_cont" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <select name="sub_type" id="sub_type_cont" class="form-select idcard-cont-field" required disabled>
                                <option value="">Select Sub Type</option>
                                <option value="Gazetted A Staff" {{ $oldSubType == 'Gazetted A Staff' ? 'selected' : '' }}>Gazetted-A Staff</option>
                                <option value="Non-Gazetted" {{ $oldSubType == 'Non-Gazetted' ? 'selected' : '' }}>Non-Gazetted</option>
                                <option value="Support Staff" {{ $oldSubType == 'Support Staff' ? 'selected' : '' }}>Support Staff</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="request_for_cont" class="form-label">Request For <span class="text-danger">*</span></label>
                            <select name="request_for" id="request_for_cont" class="form-select idcard-cont-field" required disabled>
                                <option value="">Select Request</option>
                                <option value="Own ID Card" {{ $oldRequestFor == 'Own ID Card' ? 'selected' : '' }}>Own ID Card</option>
                                <option value="Family ID Card" {{ $oldRequestFor == 'Family ID Card' ? 'selected' : '' }}>Family ID Card</option>
                                <option value="Replacement" {{ $oldRequestFor == 'Replacement' ? 'selected' : '' }}>Replacement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="name_cont" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name_cont" class="form-control idcard-cont-field" value="{{ $oldName }}" disabled required>
                        </div>
                        <div class="col-md-6">
                            <label for="designation_cont" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation_cont" class="form-control idcard-cont-field" value="{{ $oldDesignation }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="date_of_birth_cont" class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth_cont" class="form-control idcard-cont-field" value="{{ $oldDob }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="father_name_cont" class="form-label">Father Name</label>
                            <input type="text" name="father_name" id="father_name_cont" class="form-control idcard-cont-field" value="{{ old('father_name', '05/09/2013') }}" disabled>
                            @error('father_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="academy_joining_cont" class="form-label">Academy Joining</label>
                            <input type="date" name="academy_joining" id="academy_joining_cont" class="form-control idcard-cont-field" value="{{ $oldAcademy }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="id_card_valid_upto_cont" class="form-label">ID Card Valid Upto</label>
                            <input type="text" name="id_card_valid_upto" id="id_card_valid_upto_cont" class="form-control idcard-cont-field" value="{{ $oldIdValid }}" placeholder="DD/MM/YYYY" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="mobile_number_cont" class="form-label">Mobile Number</label>
                            <input type="tel" name="mobile_number" id="mobile_number_cont" class="form-control idcard-cont-field" value="{{ $oldMobile }}" placeholder="10 digit mobile number" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="vendor_organization_name_cont" class="form-label">Vendor / Organization Name</label>
                            <input type="text" name="vendor_organization_name" id="vendor_organization_name_cont" class="form-control idcard-cont-field" value="{{ old('vendor_organization_name') }}" disabled>
                            @error('vendor_organization_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="section_cont" class="form-label">Section</label>
                            <select name="section" id="section_cont" class="form-select idcard-cont-field" disabled>
                                <option value="">Select</option>
                                @foreach(['Admin', 'Finance', 'HR', 'IT', 'Academics'] as $opt)
                                    <option value="{{ $opt }}" {{ old('section') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('section')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="approval_authority_cont" class="form-label">Approval Authority</label>
                            <select name="approval_authority" id="approval_authority_cont" class="form-select idcard-cont-field" disabled>
                                <option value="">Select</option>
                                @foreach(['HOD', 'Director', 'Registrar'] as $opt)
                                    <option value="{{ $opt }}" {{ old('approval_authority') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                @endforeach
                            </select>
                            @error('approval_authority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="blood_group_cont" class="form-label">Blood Group <span class="text-danger">*</span></label>
                            <select name="blood_group" id="blood_group_cont" class="form-select idcard-cont-field" disabled required>
                                <option value="">Select Blood Group</option>
                                <option value="O+ve" {{ $oldBlood == 'O+ve' ? 'selected' : '' }}>O+ve</option>
                                <option value="O+" {{ $oldBlood == 'O+' ? 'selected' : '' }}>O+</option>
                                <option value="O-" {{ $oldBlood == 'O-' ? 'selected' : '' }}>O-</option>
                                <option value="A+" {{ $oldBlood == 'A+' ? 'selected' : '' }}>A+</option>
                                <option value="A-" {{ $oldBlood == 'A-' ? 'selected' : '' }}>A-</option>
                                <option value="B+" {{ $oldBlood == 'B+' ? 'selected' : '' }}>B+</option>
                                <option value="B-" {{ $oldBlood == 'B-' ? 'selected' : '' }}>B-</option>
                                <option value="AB+" {{ $oldBlood == 'AB+' ? 'selected' : '' }}>AB+</option>
                                <option value="AB-" {{ $oldBlood == 'AB-' ? 'selected' : '' }}>AB-</option>
                            </select>
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                            <div class="idcard-upload-zone position-relative" id="photoUploadAreaCont">
                                <input type="file" name="photo" id="photo_cont" class="d-none idcard-cont-field" accept="image/*" disabled required>
                                <div class="idcard-upload-placeholder" id="photoPlaceholderCont">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                </div>
                                <div class="idcard-upload-preview d-none" id="photoPreviewCont">
                                    <img src="" alt="Preview" class="idcard-preview-img" id="photoPreviewImgCont">
                                    <button type="button" class="idcard-preview-remove btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="photoRemoveCont" aria-label="Remove photo">&times;</button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Documents (if any)</label>
                            <div class="idcard-upload-zone position-relative" id="documentsUploadArea">
                                <input type="file" name="documents" id="documents" class="d-none @error('documents') is-invalid @enderror" accept=".pdf,.doc,.docx" disabled>
                                <div class="idcard-upload-placeholder" id="documentsPlaceholder">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                </div>
                                <div class="idcard-upload-preview idcard-doc-preview d-none" id="documentsPreview">
                                    <i class="material-icons material-symbols-rounded idcard-doc-icon">description</i>
                                    <span class="idcard-doc-name" id="documentsFileName"></span>
                                    <button type="button" class="idcard-preview-remove btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="documentsRemove" aria-label="Remove document">&times;</button>
                                </div>
                            </div>
                            @error('documents')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- Remarks (shared) -->
                <div class="row g-3 mt-1">
                    <div class="col-12">
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="3" placeholder="Add any additional remarks...">{{ old('remarks') }}</textarea>
                        @error('remarks')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- Required Fields Note -->
                <p class="small text-danger mt-4 mb-0">*Required Fields: All marked fields are mandatory for registration</p>

                <!-- Action Buttons -->
                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    <a href="{{ route('admin.employee_idcard.index') }}" class="btn btn-outline-primary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </div>
        </div>
    </form>
</div>

<style>
.idcard-create-page .breadcrumb { font-size: 0.8125rem; --bs-breadcrumb-divider: ">"; }
.idcard-create-page .breadcrumb-item a:hover { color: #004a93 !important; }
.idcard-create-type-card {
    border: 1px solid rgba(0, 74, 147, 0.25);
    border-radius: 0.5rem;
    background-color: #f0f6fc;
    box-shadow: none;
}
.idcard-radio-option { margin-bottom: 0; }
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
.idcard-form-title { font-size: 0.9375rem; font-weight: 500; color: #212529; }
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
.idcard-upload-zone:hover { background-color: #eef4fc; border-color: #004a93; }
.idcard-upload-icon { font-size: 2.5rem !important; color: #6c757d; }
.idcard-upload-zone:hover .idcard-upload-icon { color: #004a93; }
.idcard-upload-zone p { font-size: 0.875rem; color: #6c757d; margin-bottom: 0; }
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
.idcard-create-form-card .form-label { font-size: 0.875rem; color: #495057; margin-bottom: 0.35rem; }
.btn-outline-primary { border: 1px solid #004a93; color: #004a93; }
.btn-outline-primary:hover { background-color: #004a93; color: #fff; }
.idcard-upload-zone-active { background-color: #e7f1ff !important; border-color: #004a93 !important; }
.idcard-upload-zone-active .idcard-upload-icon { color: #004a93 !important; }
.idcard-upload-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 120px; }
.idcard-upload-preview { position: relative; display: flex; align-items: center; justify-content: center; min-height: 140px; padding: 0.5rem; }
.idcard-preview-img { max-width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.375rem; }
.idcard-preview-remove { width: 28px; height: 28px; padding: 0; font-size: 1.25rem; line-height: 1; border-radius: 50%; display: flex; align-items: center; justify-content: center; z-index: 2; box-shadow: 0 1px 3px rgba(0,0,0,0.2); cursor: pointer; }
.idcard-preview-remove:hover { background-color: #dc3545 !important; border-color: #dc3545 !important; color: #fff; }
.idcard-doc-preview { flex-direction: column; gap: 0.5rem; }
.idcard-doc-icon { font-size: 3rem !important; color: #6c757d; }
.idcard-doc-name { font-size: 0.875rem; color: #495057; text-align: center; word-break: break-all; padding: 0 1.5rem; }
</style>

<script>
(function() {
    'use strict';
    var form = document.getElementById('idcardForm');
    var permanentView = document.getElementById('permanent-view');
    var contractualView = document.getElementById('contractual-view');
    var permRad = document.getElementById('permanent');
    var contRad = document.getElementById('contractual');

    function showPermanent() {
        permanentView.style.display = 'block';
        contractualView.style.display = 'none';
        permanentView.querySelectorAll('.idcard-perm-field').forEach(function(el) { el.disabled = false; });
        contractualView.querySelectorAll('.idcard-cont-field').forEach(function(el) { el.disabled = true; });
        var docEl = document.getElementById('documents');
        if (docEl) docEl.disabled = true;
        var photoPerm = document.getElementById('photo_perm');
        var photoCont = document.getElementById('photo_cont');
        if (photoPerm) photoPerm.required = true;
        if (photoCont) photoCont.required = false;
    }

    function showContractual() {
        permanentView.style.display = 'none';
        contractualView.style.display = 'block';
        permanentView.querySelectorAll('.idcard-perm-field').forEach(function(el) { el.disabled = true; });
        contractualView.querySelectorAll('.idcard-cont-field').forEach(function(el) { el.disabled = false; });
        var docEl = document.getElementById('documents');
        if (docEl) docEl.disabled = false;
        var photoPerm = document.getElementById('photo_perm');
        var photoCont = document.getElementById('photo_cont');
        if (photoPerm) photoPerm.required = false;
        if (photoCont) photoCont.required = true;
    }

    permRad.addEventListener('change', showPermanent);
    contRad.addEventListener('change', showContractual);
    if (contRad.checked) showContractual();
    else showPermanent();

    function showPhotoPreview(input, placeholderId, previewId, imgId) {
        var placeholder = document.getElementById(placeholderId);
        var preview = document.getElementById(previewId);
        var img = document.getElementById(imgId);
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (placeholder) placeholder.classList.add('d-none');
        if (preview) {
            preview.classList.remove('d-none');
            if (img) {
                var reader = new FileReader();
                reader.onload = function(e) { img.src = e.target.result; };
                reader.readAsDataURL(file);
            }
        }
    }

    function showDocPreview(input, placeholderId, previewId, fileNameId) {
        var placeholder = document.getElementById(placeholderId);
        var preview = document.getElementById(previewId);
        var fileNameEl = document.getElementById(fileNameId);
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (placeholder) placeholder.classList.add('d-none');
        if (preview) {
            preview.classList.remove('d-none');
            if (fileNameEl) fileNameEl.textContent = file.name;
        }
    }

    function clearPhotoPreview(placeholderId, previewId, imgId) {
        var placeholder = document.getElementById(placeholderId);
        var preview = document.getElementById(previewId);
        var img = document.getElementById(imgId);
        if (placeholder) placeholder.classList.remove('d-none');
        if (preview) preview.classList.add('d-none');
        if (img) img.src = '';
    }

    function clearDocPreview(placeholderId, previewId, fileNameId) {
        var placeholder = document.getElementById(placeholderId);
        var preview = document.getElementById(previewId);
        var fileNameEl = document.getElementById(fileNameId);
        if (placeholder) placeholder.classList.remove('d-none');
        if (preview) preview.classList.add('d-none');
        if (fileNameEl) fileNameEl.textContent = '';
    }

    document.getElementById('photo_perm').addEventListener('change', function() {
        showPhotoPreview(this, 'photoPlaceholderPerm', 'photoPreviewPerm', 'photoPreviewImgPerm');
    });
    document.getElementById('photo_cont').addEventListener('change', function() {
        showPhotoPreview(this, 'photoPlaceholderCont', 'photoPreviewCont', 'photoPreviewImgCont');
    });
    document.getElementById('documents').addEventListener('change', function() {
        showDocPreview(this, 'documentsPlaceholder', 'documentsPreview', 'documentsFileName');
    });

    document.getElementById('photoRemovePerm').addEventListener('click', function(e) {
        e.stopPropagation();
        var input = document.getElementById('photo_perm');
        input.value = '';
        clearPhotoPreview('photoPlaceholderPerm', 'photoPreviewPerm', 'photoPreviewImgPerm');
    });
    document.getElementById('photoRemoveCont').addEventListener('click', function(e) {
        e.stopPropagation();
        var input = document.getElementById('photo_cont');
        input.value = '';
        clearPhotoPreview('photoPlaceholderCont', 'photoPreviewCont', 'photoPreviewImgCont');
    });
    document.getElementById('documentsRemove').addEventListener('click', function(e) {
        e.stopPropagation();
        var input = document.getElementById('documents');
        input.value = '';
        clearDocPreview('documentsPlaceholder', 'documentsPreview', 'documentsFileName');
    });

    var uploadAreas = [
        { areaId: 'photoUploadAreaPerm', inputId: 'photo_perm' },
        { areaId: 'photoUploadAreaCont', inputId: 'photo_cont' },
        { areaId: 'documentsUploadArea', inputId: 'documents' }
    ];
    uploadAreas.forEach(function(item) {
        var area = document.getElementById(item.areaId);
        var input = document.getElementById(item.inputId);
        if (!area || !input) return;
        area.addEventListener('click', function(e) {
            if (e.target.closest('.idcard-preview-remove')) return;
            if (!input.disabled) input.click();
        });
        area.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('idcard-upload-zone-active'); });
        area.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('idcard-upload-zone-active'); });
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            var files = e.dataTransfer.files;
            if (files.length && !input.disabled) {
                input.files = files;
                if (item.inputId === 'photo_perm') showPhotoPreview(input, 'photoPlaceholderPerm', 'photoPreviewPerm', 'photoPreviewImgPerm');
                else if (item.inputId === 'photo_cont') showPhotoPreview(input, 'photoPlaceholderCont', 'photoPreviewCont', 'photoPreviewImgCont');
                else showDocPreview(input, 'documentsPlaceholder', 'documentsPreview', 'documentsFileName');
            }
            this.classList.remove('idcard-upload-zone-active');
        });
    });
})();

(function() {
    window.addEventListener('load', function() {
        var forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    });
})();
</script>
@endsection
