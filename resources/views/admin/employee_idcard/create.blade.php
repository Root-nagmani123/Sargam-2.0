@extends('admin.layouts.master')
@section('title', 'Generate New ID Card - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid idcard-create-page">
    <x-breadcrum title="Generate New ID Card"></x-breadcrum>

    <form action="{{ route('admin.employee_idcard.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" id="idcardForm" novalidate>
        @csrf

            <!-- Employee Type Selection Card - Bootstrap 5.3 -->
        <div class="card idcard-create-type-card mb-4 border-0 shadow-sm overflow-hidden">
            <div class="card-body py-4 px-4">
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

        <!-- Request Form Card - Bootstrap 5.3 -->
        <div class="card idcard-create-form-card mb-4 border-0 shadow overflow-hidden">
            <div class="card-body p-4 p-lg-5">
                <h6 class="idcard-form-title mb-4">Please add the Request For Employee ID Card</h6>

                @php
                    $cardTypes = $cardTypes ?? [];
                    $oldCardType = old('card_type', '');
                    $oldSubType = old('sub_type', '');
                    $oldRequestFor = old('request_for', '');
                    $oldName = old('name', '');
                    $oldDesignation = old('designation', '');
                    $oldDob = old('date_of_birth', '');
                    $oldAcademy = old('academy_joining', '');
                    $oldIdValid = old('id_card_valid_upto', '');
                    $oldMobile = old('mobile_number', '');
                    $oldBlood = old('blood_group', '');
                @endphp
                <input type="hidden" name="employee_master_pk" id="employee_master_pk_input" value="{{ old('employee_master_pk') }}">

                <!-- ========== PERMANENT EMPLOYEE VIEW ========== -->
                <div id="permanent-view" class="idcard-view-fields">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="card_type_perm" class="form-label">Card Type <span class="text-danger">*</span></label>
                            <select name="card_type" id="card_type_perm" class="form-select idcard-perm-field idcard-step-field" data-field="card_type" required>
                                <option value="">Select Card Type</option>
                                @foreach($cardTypes as $ct)
                                    <option value="{{ $ct }}" {{ $oldCardType == $ct ? 'selected' : '' }}>{{ $ct }}</option>
                                @endforeach
                            </select>
                            @error('card_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="sub_type_perm" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <select name="sub_type" id="sub_type_perm" class="form-select idcard-perm-field idcard-step-field" data-field="sub_type" required>
                                <option value="">Select Sub Type</option>
                            </select>
                            @error('sub_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="request_for_perm" class="form-label">Request For <span class="text-danger">*</span></label>
                            <select name="request_for" id="request_for_perm" class="form-select idcard-perm-field idcard-step-field" data-field="request_for" required>
                                <option value="">Select Request</option>
                                <option value="Own ID Card" {{ $oldRequestFor == 'Own ID Card' ? 'selected' : '' }}>Own ID Card</option>
                            </select>
                            @error('request_for')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 duplication-extension-field" id="duplicationExtensionPerm" style="display:none;">
                        </div>
                        <div class="col-md-6">
                            <label for="name_perm" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="name" value="{{ $oldName }}" required>
                            @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="designation_perm" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="designation" value="{{ $oldDesignation }}">
                            @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="father_name_perm" class="form-label">Father Name</label>
                            <input type="text" name="father_name" id="father_name_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="father_name" value="{{ old('father_name', '') }}">
                            @error('father_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="date_of_birth_perm" class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="date_of_birth" value="{{ $oldDob }}">
                            @error('date_of_birth')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="academy_joining_perm" class="form-label">Academy Joining</label>
                            <input type="date" name="academy_joining" id="academy_joining_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="academy_joining" value="{{ $oldAcademy }}">
                            @error('academy_joining')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="mobile_number_perm" class="form-label">Mobile Number</label>
                            <input type="tel" name="mobile_number" id="mobile_number_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="mobile_number" value="{{ $oldMobile }}" placeholder="10 digit mobile number">
                            @error('mobile_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="telephone_number_perm" class="form-label">Telephone Number</label>
                            <input type="tel" name="telephone_number" id="telephone_number_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="telephone_number" value="{{ old('telephone_number', '') }}">
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
                        <div class="col-6">
                            <label for="id_card_valid_upto_perm" class="form-label">ID Card Valid Upto</label>
                            <input type="text" name="id_card_valid_upto" id="id_card_valid_upto_perm" class="form-control idcard-perm-field idcard-autofill-field" data-field="id_card_valid_upto" value="{{ $oldIdValid }}" placeholder="DD/MM/YYYY">
                            @error('id_card_valid_upto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Upload Photo <span class="text-danger">*</span></label>
                            <label for="photo_perm" class="idcard-upload-zone position-relative d-block cursor-pointer mb-0" id="photoUploadAreaPerm" style="cursor:pointer;">
                                <input type="file" name="photo" id="photo_perm" class="d-none idcard-perm-field" data-field="photo" accept="image/*" required>
                                <div class="idcard-upload-placeholder" id="photoPlaceholderPerm">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                </div>
                                <div class="idcard-upload-preview d-none" id="photoPreviewPerm">
                                    <img src="" alt="Preview" class="idcard-preview-img" id="photoPreviewImgPerm">
                                    <span class="idcard-preview-remove btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="photoRemovePerm" aria-label="Remove photo" role="button" tabindex="0">&times;</span>
                                </div>
                            </label>
                            <small class="text-muted">Photo should be in JPG/PNG format and should be less than 2MB</small>
                            @error('photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-6">
                            <label class="form-label">Upload Joining Letter <span class="text-danger">*</span></label>
                            <label for="joining_letter_perm" class="idcard-upload-zone position-relative d-block cursor-pointer mb-0" id="joiningLetterUploadAreaPerm" style="cursor:pointer;">
                                <input type="file" name="joining_letter" id="joining_letter_perm" class="d-none idcard-perm-field" data-field="joining_letter" accept=".pdf,.doc,.docx">
                                <div class="idcard-upload-placeholder" id="joiningLetterPlaceholderPerm">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                </div>
                                <div class="idcard-upload-preview idcard-doc-preview d-none" id="joiningLetterPreviewPerm">
                                    <i class="material-icons material-symbols-rounded idcard-doc-icon">description</i>
                                    <span class="idcard-doc-name" id="joiningLetterFileNamePerm"></span>
                                    <div class="d-flex gap-2 justify-content-center flex-wrap">
                                        <button type="button" class="btn btn-sm btn-outline-primary" id="joiningLetterPreviewBtnPerm" aria-label="Preview joining letter">
                                            <i class="material-icons material-symbols-rounded" style="font-size:1rem;vertical-align:middle;">visibility</i> Preview
                                        </button>
                                        <span class="idcard-preview-remove btn btn-sm btn-danger" id="joiningLetterRemovePerm" aria-label="Remove joining letter" role="button" tabindex="0">&times;</span>
                                    </div>
                                </div>
                            </label>
                            @error('joining_letter')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <!-- ========== CONTRACTUAL EMPLOYEE VIEW ========== -->
                <div id="contractual-view" class="idcard-view-fields" style="display: none;">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="card_type_cont" class="form-label">Card Type <span class="text-danger">*</span></label>
                            <select name="card_type" id="card_type_cont" class="form-select idcard-cont-field idcard-step-field" required disabled>
                                <option value="">Select Card Type</option>
                                @foreach($cardTypes as $ct)
                                    <option value="{{ $ct }}" {{ $oldCardType == $ct ? 'selected' : '' }}>{{ $ct }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="sub_type_cont" class="form-label">Sub Type <span class="text-danger">*</span></label>
                            <select name="sub_type" id="sub_type_cont" class="form-select idcard-cont-field idcard-step-field" required disabled>
                                <option value="">Select Sub Type</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="request_for_cont" class="form-label">Request For <span class="text-danger">*</span></label>
                            <select name="request_for" id="request_for_cont" class="form-select idcard-cont-field idcard-step-field" required disabled>
                                <option value="">---Select---</option>
                                <option value="Others ID Card" {{ $oldRequestFor == 'Others ID Card' ? 'selected' : '' }}>Others ID Card</option>
                            </select>
                        </div>
                        <div class="col-12 duplication-extension-field" id="duplicationExtensionCont" style="display:none;">
                        </div>
                        <!-- Contractual: Left col = Name, DOB, Academy Joining, Mobile, Section | Right col = Designation, Father Name, ID Card Valid upto, Vender/Org, Approval Authority -->
                        <div class="col-md-6">
                            <label for="name_cont" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name_cont" class="form-control idcard-cont-field idcard-autofill-field" value="{{ $oldName }}" disabled required placeholder="Name">
                        </div>
                        <div class="col-md-6">
                            <label for="designation_cont" class="form-label">Designation</label>
                            <input type="text" name="designation" id="designation_cont" class="form-control idcard-cont-field idcard-autofill-field" value="{{ $oldDesignation }}" disabled placeholder="Designation">
                        </div>
                        <div class="col-md-6">
                            <label for="date_of_birth_cont" class="form-label">Date Of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth_cont" class="form-control idcard-cont-field idcard-autofill-field" value="{{ $oldDob }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="father_name_cont" class="form-label">Father Name</label>
                            <input type="text" name="father_name" id="father_name_cont" class="form-control idcard-cont-field idcard-autofill-field" value="{{ old('father_name', '') }}" disabled placeholder="Father Name">
                            @error('father_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="academy_joining_cont" class="form-label">Academy Joining</label>
                            <input type="date" name="academy_joining" id="academy_joining_cont" class="form-control idcard-cont-field idcard-autofill-field" value="{{ $oldAcademy }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="id_card_valid_upto_cont" class="form-label">ID Card Valid Upto</label>
                            <input type="date" name="id_card_valid_upto" id="id_card_valid_upto_cont" class="form-control idcard-cont-field idcard-autofill-field" value="{{ $oldIdValid }}" disabled>
                        </div>
                        <div class="col-md-6">
                            <label for="mobile_number_cont" class="form-label">Mobile Number</label>
                            <input type="tel" name="mobile_number" id="mobile_number_cont" class="form-control idcard-cont-field idcard-autofill-field" value="{{ $oldMobile }}" placeholder="10 digit mobile number" disabled>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="vendor_organization_name_cont" class="form-label">Vender / Organization Name</label>
                            <input type="text" name="vendor_organization_name" id="vendor_organization_name_cont" class="form-control idcard-cont-field" value="{{ old('vendor_organization_name') }}" disabled placeholder="Vender / Organization Name">
                            @error('vendor_organization_name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="section_cont" class="form-label">Section</label>
                            <select name="section" id="section_cont" class="form-select idcard-cont-field" disabled>
                                <option value="">--Select--</option>
                                @if(!empty($userDepartmentName))
                                    <option value="{{ $userDepartmentName }}" {{ old('section', $userDepartmentName) == $userDepartmentName ? 'selected' : '' }}>{{ $userDepartmentName }}</option>
                                @endif
                            </select>
                            @error('section')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="approval_authority_cont" class="form-label">Approval Authority</label>
                            <select name="approval_authority" id="approval_authority_cont" class="form-select idcard-cont-field" disabled>
                                <option value="">--Select--</option>
                                @foreach($approvalAuthorityEmployees ?? [] as $emp)
                                    @php $empName = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')); @endphp
                                    <option value="{{ $emp->pk }}" {{ old('approval_authority') == $emp->pk ? 'selected' : '' }}>{{ $empName }}{{ $emp->designation ? ' (' . $emp->designation->designation_name . ')' : '' }}</option>
                                @endforeach
                            </select>
                            @error('approval_authority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="blood_group_cont" class="form-label">Blood Group <span class="text-danger">*</span></label>
                            <select name="blood_group" id="blood_group_cont" class="form-select idcard-cont-field"  required>
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
                            <label for="photo_cont" class="idcard-upload-zone position-relative d-block cursor-pointer mb-0" id="photoUploadAreaCont" style="cursor:pointer;">
                                <input type="file" name="photo" id="photo_cont" class="d-none idcard-cont-field" accept="image/*" required>
                                <div class="idcard-upload-placeholder" id="photoPlaceholderCont">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                </div>
                                <div class="idcard-upload-preview d-none" id="photoPreviewCont">
                                    <img src="" alt="Preview" class="idcard-preview-img" id="photoPreviewImgCont">
                                    <span class="idcard-preview-remove btn btn-sm btn-danger position-absolute top-0 end-0 m-1" id="photoRemoveCont" aria-label="Remove photo" role="button" tabindex="0">&times;</span>
                                </div>
                            </label>
                        </div>
                        
                    </div>
                </div>

               
                <!-- Required Fields Note -->
                <p class="small text-danger mt-4 mb-0">*Required Fields: All marked fields are mandatory for registration</p>

                <!-- Action Buttons - Bootstrap 5.3 -->
                <div class="d-flex gap-2 justify-content-end mt-4 pt-4 border-top">
                    <a href="{{ route('admin.employee_idcard.index') }}" class="btn btn-outline-secondary px-4 rounded-2">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4 rounded-2">
                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:18px;">save</i>
                        Save
                    </button>
                </div>
            </div>
        </div>

        <!-- Duplication / Extension Modal (inside form for submission) -->
    <div class="modal fade" id="duplicationExtensionModal" tabindex="-1" aria-labelledby="duplicationExtensionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-3 overflow-hidden">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="duplicationExtensionModalLabel">
                        <i class="material-icons material-symbols-rounded align-middle me-2">content_copy</i>
                        Add Duplicate / Extension ID Card Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">Fill the following details for Duplication (Expired / Lost / Damaged) or Extension requests.</p>
                    <div class="row g-3">
                        <div class="col-md-6" id="duplicationReasonField">
                            <label for="duplication_reason_modal" class="form-label">Reason for Applying Duplicate Card <span class="text-danger">*</span></label>
                            <select name="duplication_reason" id="duplication_reason_modal" class="form-select">
                                <option value="">Select Reason</option>
                                <option value="Expired Card" {{ old('duplication_reason') == 'Expired Card' ? 'selected' : '' }}>Expired Card</option>
                                <option value="Lost" {{ old('duplication_reason') == 'Lost' ? 'selected' : '' }}>Card Lost</option>
                                <option value="Damage" {{ old('duplication_reason') == 'Damage' ? 'selected' : '' }}>Card Damaged</option>
                            </select>
                            @error('duplication_reason')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="id_card_number_modal" class="form-label">ID Card Number</label>
                            <input type="text" name="id_card_number" id="id_card_number_modal" class="form-control" value="{{ old('id_card_number') }}" placeholder="e.g. NOP00148">
                            @error('id_card_number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="id_card_valid_from_modal" class="form-label">ID Card Valid From</label>
                            <input type="text" name="id_card_valid_from" id="id_card_valid_from_modal" class="form-control" value="{{ old('id_card_valid_from') }}" placeholder="DD/MM/YYYY">
                            @error('id_card_valid_from')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="id_card_valid_upto_modal" class="form-label">ID Card Valid Upto <span class="text-muted">(New validity for Extension)</span></label>
                            <input type="text" name="id_card_valid_upto" id="id_card_valid_upto_modal" class="form-control" value="{{ old('id_card_valid_upto', $oldIdValid ?? '') }}" placeholder="DD/MM/YYYY">
                            @error('id_card_valid_upto')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6" id="firReceiptField" style="display:none;">
                            <label class="form-label">Upload FIR (First Information Report) <span class="text-danger">*</span> <span class="text-muted">(Required when Card Lost)</span></label>
                            <div class="idcard-upload-zone position-relative" id="firReceiptUploadArea">
                                <input type="file" name="fir_receipt" id="fir_receipt_modal" class="d-none" accept=".pdf,.doc,.docx,image/*">
                                <div class="idcard-upload-placeholder" id="firReceiptPlaceholder">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0 small">Upload FIR filed against lost card</p>
                                </div>
                                <div class="idcard-upload-preview idcard-doc-preview d-none" id="firReceiptPreview">
                                    <i class="material-icons material-symbols-rounded idcard-doc-icon">description</i>
                                    <span class="idcard-doc-name" id="firReceiptFileName"></span>
                                    <button type="button" class="idcard-preview-remove btn btn-sm btn-danger" id="firReceiptRemove">&times;</button>
                                </div>
                            </div>
                            @error('fir_receipt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Upload Payment Receipt</label>
                            <div class="idcard-upload-zone position-relative" id="paymentReceiptUploadArea">
                                <input type="file" name="payment_receipt" id="payment_receipt_modal" class="d-none" accept=".pdf,.doc,.docx,image/*">
                                <div class="idcard-upload-placeholder" id="paymentReceiptPlaceholder">
                                    <i class="material-icons material-symbols-rounded idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0 small">Click to upload</p>
                                </div>
                                <div class="idcard-upload-preview idcard-doc-preview d-none" id="paymentReceiptPreview">
                                    <i class="material-icons material-symbols-rounded idcard-doc-icon">description</i>
                                    <span class="idcard-doc-name" id="paymentReceiptFileName"></span>
                                    <button type="button" class="idcard-preview-remove btn btn-sm btn-danger" id="paymentReceiptRemove">&times;</button>
                                </div>
                            </div>
                            @error('payment_receipt')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="mt-4 pt-3 border-top">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done</button>
                    </div>
                </div>
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
.duplication-reason-field .form-select.w-auto { min-width: 180px; }
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
        contractualView.querySelectorAll('.idcard-cont-field').forEach(function(el) {
            if (!(el.tagName === 'INPUT' && el.type === 'file')) el.disabled = true;
        });
        var photoPerm = document.getElementById('photo_perm');
        var photoCont = document.getElementById('photo_cont');
        if (photoPerm) photoPerm.required = true;
        if (photoCont) photoCont.required = false;
    }

    function showContractual() {
        permanentView.style.display = 'none';
        contractualView.style.display = 'block';
        permanentView.querySelectorAll('.idcard-perm-field').forEach(function(el) {
            if (!(el.tagName === 'INPUT' && el.type === 'file')) el.disabled = true;
        });
        contractualView.querySelectorAll('.idcard-cont-field').forEach(function(el) { el.disabled = false; });
        var photoPerm = document.getElementById('photo_perm');
        var photoCont = document.getElementById('photo_cont');
        if (photoPerm) photoPerm.required = false;
        if (photoCont) photoCont.required = true;
    }

    permRad.addEventListener('change', function() { showPermanent(); idcardResetFlow(); });
    contRad.addEventListener('change', function() { showContractual(); idcardResetFlow(); });
    if (contRad.checked) showContractual();
    else showPermanent();

    var subTypesUrl = '{{ route("admin.employee_idcard.subTypes") }}';
    var meUrl = '{{ route("admin.employee_idcard.me") }}';

    function idcardGetStepFields() {
        var isPerm = permanentView.style.display !== 'none';
        return {
            cardType: document.getElementById(isPerm ? 'card_type_perm' : 'card_type_cont'),
            subType: document.getElementById(isPerm ? 'sub_type_perm' : 'sub_type_cont'),
            requestFor: document.getElementById(isPerm ? 'request_for_perm' : 'request_for_cont'),
            isPerm: isPerm
        };
    }

    function idcardResetFlow() {
        var step = idcardGetStepFields();
        step.subType.innerHTML = '<option value="">Select Sub Type</option>';
        step.subType.disabled = true;
        step.requestFor.disabled = true;
        step.requestFor.value = '';
        var autofillFields = (step.isPerm ? permanentView : contractualView).querySelectorAll('.idcard-autofill-field');
        autofillFields.forEach(function(el) {
            el.disabled = true;
            el.removeAttribute('readonly');
            el.value = '';
        });
        document.getElementById('employee_master_pk_input').value = '';
        var bloodPerm = document.getElementById('blood_group_perm');
        var bloodCont = document.getElementById('blood_group_cont');
        if (bloodPerm) bloodPerm.value = '';
        if (bloodCont) bloodCont.value = '';
        idcardDisableAutofillExceptStep();
    }

    function idcardDisableAutofillExceptStep() {
        var step = idcardGetStepFields();
        var view = step.isPerm ? permanentView : contractualView;
        view.querySelectorAll('.idcard-autofill-field').forEach(function(el) { el.disabled = true; });
        // Photo and Joining Letter: keep enabled so user can always select file when this view is visible
        // Blood Group: always enabled so user can select (never disabled)
    }

    function idcardEnableOnlyPhotoAndBlood() {
        var step = idcardGetStepFields();
        var view = step.isPerm ? permanentView : contractualView;
        view.querySelectorAll('.idcard-autofill-field').forEach(function(el) {
            el.disabled = false;
            if (el.tagName === 'INPUT' && el.type !== 'hidden' && (el.type === 'text' || el.type === 'date' || el.type === 'tel')) el.readOnly = true;
        });
        var photoInput = document.getElementById(step.isPerm ? 'photo_perm' : 'photo_cont');
        var joinInput = document.getElementById(step.isPerm ? 'joining_letter_perm' : 'joining_letter_cont');
        if (photoInput) { photoInput.disabled = false; photoInput.required = true; }
        if (joinInput) joinInput.disabled = false;
        if (step.isPerm) {
            var bg = document.getElementById('blood_group_perm');
            if (bg) { bg.disabled = false; bg.required = true; }
        } else {
            var bg = document.getElementById('blood_group_cont');
            if (bg) { bg.disabled = false; bg.required = true; }
        }
    }

    function idcardLoadSubTypes() {
        var step = idcardGetStepFields();
        var cardType = step.cardType.value;
        var employeeType = document.getElementById('permanent').checked ? 'Permanent Employee' : 'Contractual Employee';
        if (!cardType) {
            step.subType.innerHTML = '<option value="">Select Sub Type</option>';
            step.subType.disabled = true;
            step.requestFor.disabled = true;
            return;
        }
        fetch(subTypesUrl + '?card_type=' + encodeURIComponent(cardType) + '&employee_type=' + encodeURIComponent(employeeType))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                step.subType.innerHTML = '<option value="">Select Sub Type</option>';
                (data.sub_types || []).forEach(function(o) {
                    var opt = document.createElement('option');
                    opt.value = o.value;
                    opt.textContent = o.text;
                    step.subType.appendChild(opt);
                });
                step.subType.disabled = false;
                step.subType.value = '';
                step.requestFor.disabled = true;
            })
            .catch(function() {
                step.subType.innerHTML = '<option value="">Select Sub Type</option>';
                step.subType.disabled = false;
            });
    }

    function idcardLoadMe() {
        var step = idcardGetStepFields();
        if (step.requestFor.value !== 'Own ID Card') return;
        fetch(meUrl)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var emp = data.employee;
                if (!emp) return;
                document.getElementById('employee_master_pk_input').value = emp.employee_master_pk || '';
                var view = step.isPerm ? permanentView : contractualView;
                var set = function(id, val) {
                    var el = document.getElementById(id);
                    if (el) el.value = val || '';
                };
                if (step.isPerm) {
                    set('name_perm', emp.name);
                    set('designation_perm', emp.designation);
                    set('date_of_birth_perm', emp.date_of_birth);
                    set('father_name_perm', emp.father_name);
                    set('academy_joining_perm', emp.academy_joining);
                    set('mobile_number_perm', emp.mobile_number);
                    set('telephone_number_perm', emp.telephone_number);
                    set('id_card_valid_upto_perm', emp.id_card_valid_upto);
                } else {
                    set('name_cont', emp.name);
                    set('designation_cont', emp.designation);
                    set('date_of_birth_cont', emp.date_of_birth);
                    set('father_name_cont', emp.father_name);
                    set('academy_joining_cont', emp.academy_joining);
                    set('mobile_number_cont', emp.mobile_number);
                    set('id_card_valid_upto_cont', emp.id_card_valid_upto);
                }
                idcardEnableOnlyPhotoAndBlood();
            })
            .catch(function() {});
    }

    document.getElementById('card_type_perm').addEventListener('change', function() {
        if (permanentView.style.display !== 'none') idcardLoadSubTypes();
    });
    document.getElementById('card_type_cont').addEventListener('change', function() {
        if (contractualView.style.display !== 'none') idcardLoadSubTypes();
    });
    document.getElementById('sub_type_perm').addEventListener('change', function() {
        if (permanentView.style.display !== 'none') {
            document.getElementById('request_for_perm').disabled = !document.getElementById('sub_type_perm').value;
        }
    });
    document.getElementById('sub_type_cont').addEventListener('change', function() {
        if (contractualView.style.display !== 'none') {
            document.getElementById('request_for_cont').disabled = !document.getElementById('sub_type_cont').value;
        }
    });
    document.getElementById('request_for_perm').addEventListener('change', function() {
        if (permanentView.style.display !== 'none') idcardLoadMe();
    });
    document.getElementById('request_for_cont').addEventListener('change', function() {
        if (contractualView.style.display !== 'none') {
            if (this.value === 'Others ID Card') {
                contractualView.querySelectorAll('.idcard-autofill-field').forEach(function(el) { el.disabled = false; el.removeAttribute('readonly'); });
                var sec = document.getElementById('section_cont'); if (sec) sec.disabled = false;
                var app = document.getElementById('approval_authority_cont'); if (app) app.disabled = false;
                var ven = document.getElementById('vendor_organization_name_cont'); if (ven) ven.disabled = false;
            } else {
                contractualView.querySelectorAll('.idcard-autofill-field').forEach(function(el) { el.disabled = true; el.value = ''; });
                var sec = document.getElementById('section_cont'); if (sec) sec.disabled = true;
                var app = document.getElementById('approval_authority_cont'); if (app) app.disabled = true;
                var ven = document.getElementById('vendor_organization_name_cont'); if (ven) ven.disabled = true;
            }
        }
    });

    idcardDisableAutofillExceptStep();
    [ 'photo_perm', 'photo_cont', 'joining_letter_perm', 'joining_letter_cont', 'documents' ].forEach(function(id) {
        var el = document.getElementById(id);
        if (el && el.type === 'file') el.disabled = false;
    });
    if (permanentView.style.display !== 'none') {
        document.getElementById('sub_type_perm').disabled = true;
        document.getElementById('request_for_perm').disabled = true;
        if (document.getElementById('card_type_perm').value) idcardLoadSubTypes();
    } else {
        document.getElementById('sub_type_cont').disabled = true;
        document.getElementById('request_for_cont').disabled = true;
        if (document.getElementById('card_type_cont').value) idcardLoadSubTypes();
    }

    function toggleDuplicationExtension() {
        var reqPerm = document.getElementById('request_for_perm');
        var reqCont = document.getElementById('request_for_cont');
        var dupPerm = document.getElementById('duplicationExtensionPerm');
        var dupCont = document.getElementById('duplicationExtensionCont');
        var showDup = ['Replacement', 'Duplication', 'Extension'];
        var isDupExt = (reqPerm && showDup.includes(reqPerm.value)) || (reqCont && showDup.includes(reqCont.value));
        if (dupPerm) dupPerm.style.display = (reqPerm && showDup.includes(reqPerm.value)) ? '' : 'none';
        if (dupCont) dupCont.style.display = (reqCont && showDup.includes(reqCont.value)) ? '' : 'none';
        var openBtnCont = document.getElementById('openDuplicationModalCont');
        if (openBtnCont) openBtnCont.disabled = !(reqCont && showDup.includes(reqCont.value));
        var modalInputs = ['duplication_reason_modal', 'id_card_number_modal', 'id_card_valid_from_modal', 'id_card_valid_upto_modal', 'fir_receipt_modal', 'payment_receipt_modal'];
        modalInputs.forEach(function(id) {
            var el = document.getElementById(id);
            if (el) el.disabled = !isDupExt;
        });
        var permValid = document.getElementById('id_card_valid_upto_perm');
        var contValid = document.getElementById('id_card_valid_upto_cont');
        if (permValid) permValid.disabled = (reqPerm && showDup.includes(reqPerm.value));
        if (contValid) contValid.disabled = (reqCont && showDup.includes(reqCont.value));
    }
    document.getElementById('request_for_perm').addEventListener('change', toggleDuplicationExtension);
    document.getElementById('request_for_cont').addEventListener('change', toggleDuplicationExtension);
    toggleDuplicationExtension();

    document.getElementById('duplication_reason_modal').addEventListener('change', function() {
        var firField = document.getElementById('firReceiptField');
        firField.style.display = this.value === 'Lost' ? '' : 'none';
    });
    if (document.getElementById('duplication_reason_modal').value) {
        document.getElementById('duplication_reason_modal').dispatchEvent(new Event('change'));
    }

    document.getElementById('duplicationExtensionModal').addEventListener('shown.bs.modal', function() {
        document.getElementById('duplication_reason_modal').dispatchEvent(new Event('change'));
    });
    document.getElementById('duplicationExtensionModal').addEventListener('hidden.bs.modal', function() {
        var reason = document.getElementById('duplication_reason_modal').value;
        var validFrom = document.getElementById('id_card_valid_from_modal').value;
        var validUpto = document.getElementById('id_card_valid_upto_modal').value;
        var summary = [reason, validFrom, validUpto].filter(Boolean).join(' | ') || 'Not filled';
        document.getElementById('duplicationSummaryPerm').textContent = summary ? '(' + summary + ')' : '';
        document.getElementById('duplicationSummaryCont').textContent = summary ? '(' + summary + ')' : '';
        var reqPerm = document.getElementById('request_for_perm');
        if (reqPerm && ['Duplication', 'Extension'].includes(reqPerm.value)) {
            var permValid = document.getElementById('id_card_valid_upto_perm');
            if (permValid) permValid.value = validUpto;
        }
        var reqCont = document.getElementById('request_for_cont');
        if (reqCont && ['Duplication', 'Extension'].includes(reqCont.value)) {
            var contValid = document.getElementById('id_card_valid_upto_cont');
            if (contValid) contValid.value = validUpto;
        }
    });

    function showDocPreviewModal(input, placeholderId, previewId, fileNameId) {
        var placeholder = document.getElementById(placeholderId);
        var preview = document.getElementById(previewId);
        var fileNameEl = document.getElementById(fileNameId);
        if (!input.files || !input.files[0]) return;
        var file = input.files[0];
        if (placeholder) placeholder.classList.add('d-none');
        if (preview) { preview.classList.remove('d-none'); if (fileNameEl) fileNameEl.textContent = file.name; }
    }
    function clearDocPreviewModal(placeholderId, previewId, fileNameId) {
        var placeholder = document.getElementById(placeholderId);
        var preview = document.getElementById(previewId);
        var fileNameEl = document.getElementById(fileNameId);
        if (placeholder) placeholder.classList.remove('d-none');
        if (preview) preview.classList.add('d-none');
        if (fileNameEl) fileNameEl.textContent = '';
    }
    document.getElementById('fir_receipt_modal').addEventListener('change', function() {
        showDocPreviewModal(this, 'firReceiptPlaceholder', 'firReceiptPreview', 'firReceiptFileName');
    });
    document.getElementById('payment_receipt_modal').addEventListener('change', function() {
        showDocPreviewModal(this, 'paymentReceiptPlaceholder', 'paymentReceiptPreview', 'paymentReceiptFileName');
    });
    document.getElementById('firReceiptRemove').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('fir_receipt_modal').value = '';
        clearDocPreviewModal('firReceiptPlaceholder', 'firReceiptPreview', 'firReceiptFileName');
    });
    document.getElementById('paymentReceiptRemove').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('payment_receipt_modal').value = '';
        clearDocPreviewModal('paymentReceiptPlaceholder', 'paymentReceiptPreview', 'paymentReceiptFileName');
    });
    document.getElementById('firReceiptUploadArea').addEventListener('click', function(e) {
        if (!e.target.closest('button')) document.getElementById('fir_receipt_modal').click();
    });
    document.getElementById('paymentReceiptUploadArea').addEventListener('click', function(e) {
        if (!e.target.closest('button')) document.getElementById('payment_receipt_modal').click();
    });

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
    document.getElementById('joining_letter_perm').addEventListener('change', function() {
        showDocPreview(this, 'joiningLetterPlaceholderPerm', 'joiningLetterPreviewPerm', 'joiningLetterFileNamePerm');
    });
    document.getElementById('joining_letter_cont').addEventListener('change', function() {
        showDocPreview(this, 'joiningLetterPlaceholderCont', 'joiningLetterPreviewCont', 'joiningLetterFileNameCont');
    });

    function openDocPreview(inputId) {
        var input = document.getElementById(inputId);
        if (!input || !input.files || !input.files[0]) return;
        var file = input.files[0];
        var url = URL.createObjectURL(file);
        window.open(url, '_blank', 'noopener,noreferrer');
        setTimeout(function() { URL.revokeObjectURL(url); }, 60000);
    }

    document.getElementById('joiningLetterPreviewBtnPerm').addEventListener('click', function(e) {
        e.stopPropagation();
        openDocPreview('joining_letter_perm');
    });
    document.getElementById('joiningLetterPreviewBtnCont').addEventListener('click', function(e) {
        e.stopPropagation();
        openDocPreview('joining_letter_cont');
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
    document.getElementById('joiningLetterRemovePerm').addEventListener('click', function(e) {
        e.stopPropagation();
        var input = document.getElementById('joining_letter_perm');
        input.value = '';
        clearDocPreview('joiningLetterPlaceholderPerm', 'joiningLetterPreviewPerm', 'joiningLetterFileNamePerm');
    });
    document.getElementById('joiningLetterRemoveCont').addEventListener('click', function(e) {
        e.stopPropagation();
        var input = document.getElementById('joining_letter_cont');
        input.value = '';
        clearDocPreview('joiningLetterPlaceholderCont', 'joiningLetterPreviewCont', 'joiningLetterFileNameCont');
    });

    var uploadAreas = [
        { areaId: 'photoUploadAreaPerm', inputId: 'photo_perm' },
        { areaId: 'photoUploadAreaCont', inputId: 'photo_cont' },
        { areaId: 'joiningLetterUploadAreaPerm', inputId: 'joining_letter_perm' },
        { areaId: 'joiningLetterUploadAreaCont', inputId: 'joining_letter_cont' },
        { areaId: 'documentsUploadArea', inputId: 'documents' }
    ];
    uploadAreas.forEach(function(item) {
        var area = document.getElementById(item.areaId);
        var input = document.getElementById(item.inputId);
        if (!area || !input) return;
        area.addEventListener('click', function(e) {
            if (e.target.closest('button') || e.target.closest('[role="button"]')) return;
            input.disabled = false;
            if (area.tagName !== 'LABEL') input.click();
        });
        area.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('idcard-upload-zone-active'); });
        area.addEventListener('dragleave', function(e) { e.preventDefault(); this.classList.remove('idcard-upload-zone-active'); });
        area.addEventListener('drop', function(e) {
            e.preventDefault();
            var files = e.dataTransfer.files;
            if (files.length) {
                input.disabled = false;
                input.files = files;
                if (item.inputId === 'photo_perm') showPhotoPreview(input, 'photoPlaceholderPerm', 'photoPreviewPerm', 'photoPreviewImgPerm');
                else if (item.inputId === 'photo_cont') showPhotoPreview(input, 'photoPlaceholderCont', 'photoPreviewCont', 'photoPreviewImgCont');
                else if (item.inputId === 'joining_letter_perm') showDocPreview(input, 'joiningLetterPlaceholderPerm', 'joiningLetterPreviewPerm', 'joiningLetterFileNamePerm');
                else if (item.inputId === 'joining_letter_cont') showDocPreview(input, 'joiningLetterPlaceholderCont', 'joiningLetterPreviewCont', 'joiningLetterFileNameCont');
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
