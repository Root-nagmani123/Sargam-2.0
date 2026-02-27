@extends('admin.layouts.master')
@section('title', 'Generate New ID Card - Sargam | Lal Bahadur Shastri')
@section('setup_content')
<div class="container-fluid family-idcard-create-page">
    <x-breadcrum title="Generate New ID Card"></x-breadcrum>
   
    @if(count($errors) > 0)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
    <strong>Validation Error - </strong>
    <ul class="mb-0 mt-1">
        @foreach($errors->all() as $message)
        <li>{{ $message }}</li>
        @endforeach
    </ul>
   </div>
    @endif

    <form action="{{ route('admin.family_idcard.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" id="familyIdcardForm" novalidate>
        @csrf
    
        <!-- Employee Type: Government / Contractual -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body py-4 px-4">
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="employee_type" id="emp_type_govt" value="Permanent Employee"
                               {{ old('employee_type', 'Permanent Employee') == 'Permanent Employee' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="emp_type_govt">Government Employee</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="radio" name="employee_type" id="emp_type_cont" value="Contractual Employee"
                               {{ old('employee_type') == 'Contractual Employee' ? 'checked' : '' }} required>
                        <label class="form-check-label" for="emp_type_cont">Contractual Employee</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Please add the Request For Family Member ID Card.</h6>

                @php
                    $oldEmployeeId = old('employee_id', 'ITS005');
                    $oldDesignation = old('designation', 'Assistant Programmer');
                    $oldCardType = old('card_type', 'Family');
                    $oldSection = old('section', $userDepartmentName ?? 'NIELIT');
                    $oldApprovalAuthority = old('approval_authority', $defaultApprovalAuthorityPk ?? '');
                @endphp

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id" id="employee_id" class="form-control" value="{{ $oldEmployeeId }}" placeholder="Enter your ID Card No." required>
                        @error('employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" id="designation" class="form-control" value="{{ $oldDesignation }}" placeholder="Enter Designation." required>
                        @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="card_type" class="form-label">Card Type <span class="text-danger">*</span></label>
                        <select name="card_type" id="card_type" class="form-select" required>
                            <option value="">Select The Card Type</option>
                            <option value="Family" {{ $oldCardType == 'Family' ? 'selected' : '' }}>Family</option>
                            <option value="Employee" {{ $oldCardType == 'Employee' ? 'selected' : '' }}>Employee</option>
                        </select>
                        @error('card_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" name="section" id="section" class="form-control" value="{{ $oldSection }}" placeholder="Enter Section" required>
                        @error('section')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    {{-- Approval Authority: shown only when Contractual --}}
                  {{--  <div class="col-md-6 fml-approval-authority-wrap" id="fmlApprovalAuthorityWrap" style="display: none;">
                        <label for="approval_authority" class="form-label">Approval Authority <span class="text-danger">*</span></label>
                        <select name="approval_authority" id="approval_authority" class="form-select" disabled>
                            <option value="">-- Select --</option>
                            @foreach($approvalAuthorityEmployees ?? [] as $emp)
                                @php $empName = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')); @endphp
                                <option value="{{ $emp->pk }}" {{ $oldApprovalAuthority == $emp->pk ? 'selected' : '' }}>{{ $empName }}{{ $emp->designation ? ' (' . $emp->designation->designation_name . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">On behalf of your section</small>
                        @error('approval_authority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div> --}}
                    <div class="col-12">
                        <label class="form-label">Upload Group Photo <span class="text-danger">*</span></label>
                        <div class="family-idcard-upload-zone position-relative" id="groupPhotoUploadZone">
                            <input type="file" name="group_photo" id="group_photo" class="d-none" accept="image/*" required>
                            <div class="family-idcard-upload-placeholder" id="groupPhotoPlaceholder">
                                <i class="material-icons material-symbols-rounded family-idcard-upload-icon">upload</i>
                                <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                <span class="small text-muted">One photo for all family members. Allowed: JPG, PNG, GIF. Max size: 2 MB</span>
                            </div>
                            <div class="family-idcard-upload-preview d-none position-relative" id="groupPhotoPreview">
                                <img src="" alt="Group Photo Preview" class="family-idcard-preview-img" id="groupPhotoPreviewImg">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 family-idcard-preview-remove" id="groupPhotoRemove" aria-label="Remove photo">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">close</i>
                                </button>
                            </div>
                        </div>
                        @error('group_photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- Family Members List (appendable rows) -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-semibold mb-0">Family Members List</h6>
                    <button type="button" class="btn btn-primary btn-sm" id="addFamilyMemberBtn">
                        <i class="material-icons material-symbols-rounded align-middle me-1" style="font-size:18px;">add</i>
                        Add Family Member
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered family-idcard-members-table mb-0" id="familyMembersTable">
                        <thead>
                            <tr>
                                <th style="width: 60px;">S.No.</th>
                                <th>Name <span class="text-danger">*</span></th>
                                <th>Relation</th>
                                <th>DOB</th>
                                <th>Valid From</th>
                                <th>Valid To</th>
                                <th>Individual Photo <span class="text-danger">*</span></th>
                                <th style="width: 70px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="familyMembersBody">
                            @php
                                $relationOptions = ['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Brother', 'Sister', 'Other'];
                            @endphp
                            <!-- First row -->
                            <tr class="family-member-row" data-row-index="0">
                                <td class="align-middle fw-medium row-sno">1</td>
                                <td class="align-middle">
                                    <input type="text" name="members[0][name]" class="form-control form-control-sm member-name" placeholder="Name" required>
                                    @error('members.0.name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </td>
                                <td class="align-middle">
                                    <select name="members[0][relation]" class="form-select form-select-sm">
                                        <option value="">Select Relation</option>
                                        @foreach($relationOptions as $opt)
                                            <option value="{{ $opt }}" {{ old('members.0.relation') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="align-middle">
                                    <input type="date" name="members[0][dob]" class="form-control form-control-sm" placeholder="DOB">
                                </td>
                                <td class="align-middle">
                                    <input type="date" name="members[0][valid_from]" class="form-control form-control-sm valid-from-field" min="{{ date('Y-m-d') }}">
                                </td>
                                <td class="align-middle">
                                    <input type="date" name="members[0][valid_to]" class="form-control form-control-sm">
                                </td>
                                <td class="align-middle">
                                    <div class="family-idcard-upload-zone-sm position-relative member-photo-cell" data-row="0">
                                        <input type="file" name="members[0][family_photo]" class="d-none member-photo-input" accept=".jpeg,.jpg,.png" required data-row="0">
                                        <div class="family-idcard-upload-placeholder-sm" data-placeholder="0">
                                            <i class="material-icons material-symbols-rounded" style="font-size:1.5rem; color:#6c757d;">upload</i>
                                            <span class="small d-block mt-1">Upload</span>
                                            <span class="small text-muted d-block">JPG, PNG. Max 2 MB</span>
                                        </div>
                                        <div class="family-idcard-upload-preview-sm d-none position-relative" data-preview="0">
                                            <img src="" alt="Preview" class="member-preview-img" data-img="0" style="max-height:60px; border-radius:4px;">
                                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-0 p-0 member-photo-remove" style="width:22px; height:22px; font-size:14px; line-height:1; border-radius:50%;" data-row="0" aria-label="Remove">&times;</button>
                                        </div>
                                    </div>
                                </td>
                                <td class="align-middle text-center">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-member-btn" data-row="0" title="Remove row" aria-label="Remove row">
                                        <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p id="noMembersMsg" class="small text-muted mt-2 d-none">Add at least one family member using the button above.</p>

                <p class="small text-danger mt-4 mb-0">*Required Fields: All marked fields are mandatory for registration.</p>

                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-outline-primary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Save</button>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="familyMemberRowTemplate">
@verbatim
    <tr class="family-member-row" data-row-index="{{INDEX}}">
        <td class="align-middle fw-medium row-sno">{{SNO}}</td>
        <td class="align-middle">
            <input type="text" name="members[{{INDEX}}][name]" class="form-control form-control-sm member-name" placeholder="Name" required>
        </td>
        <td class="align-middle">
            <select name="members[{{INDEX}}][relation]" class="form-select form-select-sm member-relation">
                <option value="">Select Relation</option>
                <option value="Spouse">Spouse</option>
                <option value="Son">Son</option>
                <option value="Daughter">Daughter</option>
                <option value="Father">Father</option>
                <option value="Mother">Mother</option>
                <option value="Brother">Brother</option>
                <option value="Sister">Sister</option>
                <option value="Other">Other</option>
            </select>
        </td>
        <td class="align-middle">
            <input type="date" name="members[{{INDEX}}][dob]" class="form-control form-control-sm">
        </td>
        <td class="align-middle">
            <input type="date" name="members[{{INDEX}}][valid_from]" class="form-control form-control-sm valid-from-field" min="{{ date('Y-m-d') }}">
        </td>
        <td class="align-middle">
            <input type="date" name="members[{{INDEX}}][valid_to]" class="form-control form-control-sm">
        </td>
        <td class="align-middle">
            <div class="family-idcard-upload-zone-sm position-relative member-photo-cell" data-row="{{INDEX}}">
                <input type="file" name="members[{{INDEX}}][family_photo]" class="d-none member-photo-input" accept=".jpeg,.jpg,.png" required data-row="{{INDEX}}">
                <div class="family-idcard-upload-placeholder-sm" data-placeholder="{{INDEX}}">
                    <i class="material-icons material-symbols-rounded" style="font-size:1.5rem; color:#6c757d;">upload</i>
                    <span class="small d-block mt-1">Upload</span>
                    <span class="small text-muted d-block">JPG, PNG. Max 2 MB</span>
                </div>
                <div class="family-idcard-upload-preview-sm d-none position-relative" data-preview="{{INDEX}}">
                    <img src="" alt="Preview" class="member-preview-img" data-img="{{INDEX}}" style="max-height:60px; border-radius:4px;">
                    <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-0 p-0 member-photo-remove" style="width:22px; height:22px; font-size:14px; line-height:1; border-radius:50%;" data-row="{{INDEX}}" aria-label="Remove">&times;</button>
                </div>
            </div>
        </td>
        <td class="align-middle text-center">
            <button type="button" class="btn btn-outline-danger btn-sm remove-member-btn" data-row="{{INDEX}}" title="Remove row" aria-label="Remove row">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
            </button>
        </td>
    </tr>
@endverbatim
</template>

<style>
.family-idcard-create-page .form-control,
.family-idcard-create-page .form-select {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    font-size: 0.9375rem;
}
.family-idcard-create-page .form-control:focus,
.family-idcard-create-page .form-select:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.15);
}
.family-idcard-upload-zone {
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
.family-idcard-upload-zone:hover {
    background-color: #eef4fc;
    border-color: #004a93;
}
.family-idcard-upload-icon { font-size: 2.5rem !important; color: #6c757d; }
.family-idcard-upload-zone:hover .family-idcard-upload-icon { color: #004a93; }
.family-idcard-upload-placeholder { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 120px; }
.family-idcard-upload-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 160px;
    padding: 0.5rem;
    position: relative;
}
.family-idcard-preview-img {
    max-width: 100%;
    max-height: 180px;
    object-fit: contain;
    border-radius: 0.375rem;
}
.family-idcard-preview-remove {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.family-idcard-preview-remove:hover { background-color: #dc3545 !important; color: #fff; }
.family-idcard-upload-zone-sm {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    min-height: 80px;
    min-width: 90px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.family-idcard-upload-zone-sm:hover {
    background-color: #eef4fc;
    border-color: #004a93;
}
.family-idcard-upload-placeholder-sm { display: flex; flex-direction: column; align-items: center; justify-content: center; }
.family-idcard-upload-preview-sm { display: flex; align-items: center; justify-content: center; padding: 0.25rem; position: relative; min-height: 80px; }
.family-idcard-members-table thead tr { background: #122442; color: #fff; }
.family-idcard-members-table thead th { font-weight: 600; font-size: 0.8125rem; padding: 0.75rem 0.5rem; border: none; }
.family-idcard-members-table tbody td { padding: 0.5rem; vertical-align: middle; }
.btn-outline-primary { border: 1px solid #004a93; color: #004a93; }
.btn-outline-primary:hover { background-color: #004a93; color: #fff; }
</style>

<script>
(function() {
    'use strict';

    // Employee type toggle: show Approval Authority when Contractual
    function toggleFmlApprovalAuthority() {
        var contRad = document.getElementById('emp_type_cont');
        var wrap = document.getElementById('fmlApprovalAuthorityWrap');
        var sel = document.getElementById('approval_authority');
        if (!contRad || !wrap || !sel) return;
        if (contRad.checked) {
            wrap.style.display = 'block';
            sel.disabled = false;
            sel.required = true;
        } else {
            wrap.style.display = 'none';
            sel.disabled = true;
            sel.required = false;
            sel.value = '';
        }
    }
    document.getElementById('emp_type_govt')?.addEventListener('change', toggleFmlApprovalAuthority);
    document.getElementById('emp_type_cont')?.addEventListener('change', toggleFmlApprovalAuthority);
    toggleFmlApprovalAuthority();

    var tbody = document.getElementById('familyMembersBody');
    var addBtn = document.getElementById('addFamilyMemberBtn');
    var template = document.getElementById('familyMemberRowTemplate');
    var rowIndex = 1;

    function updateRowNumbers() {
        var rows = tbody.querySelectorAll('.family-member-row');
        rows.forEach(function(row, i) {
            row.setAttribute('data-row-index', i);
            var sno = row.querySelector('.row-sno');
            if (sno) sno.textContent = i + 1;
            row.querySelectorAll('[name]').forEach(function(inp) {
                var name = inp.getAttribute('name');
                if (name) {
                    name = name.replace(/members\[\d+\]/, 'members[' + i + ']');
                    inp.setAttribute('name', name);
                }
            });
            row.querySelectorAll('.member-photo-input, .member-photo-cell, [data-placeholder], [data-preview], .member-preview-img, .member-photo-remove, .remove-member-btn').forEach(function(el) {
                if (el.hasAttribute('data-row')) el.setAttribute('data-row', i);
                if (el.getAttribute('data-placeholder') !== null) el.setAttribute('data-placeholder', i);
                if (el.getAttribute('data-preview') !== null) el.setAttribute('data-preview', i);
                if (el.getAttribute('data-img') !== null) el.setAttribute('data-img', i);
            });
        });
    }

    function addRow() {
        var html = template.innerHTML
            .replace(/\{\{INDEX\}\}/g, rowIndex)
            .replace(/\{\{SNO\}\}/g, rowIndex + 1);
        tbody.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        updateRowNumbers();
        bindRowEvents(tbody.querySelector('.family-member-row:last-child'));
    }

    function bindRowEvents(row) {
        if (!row) return;
        var idx = row.getAttribute('data-row-index');
        var photoCell = row.querySelector('.member-photo-cell');
        var input = row.querySelector('.member-photo-input');
        var placeholder = row.querySelector('[data-placeholder="' + idx + '"]');
        var preview = row.querySelector('[data-preview="' + idx + '"]');
        var img = row.querySelector('.member-preview-img[data-img="' + idx + '"]');
        var removePhotoBtn = row.querySelector('.member-photo-remove');
        var removeRowBtn = row.querySelector('.remove-member-btn');
        var nameInput = row.querySelector('.member-name');

        // Real-time duplicate checking for member name
        if (nameInput) {
            nameInput.addEventListener('blur', function() {
                var currentName = this.value.trim().toLowerCase();
                var isDuplicate = false;
                
                if (currentName) {
                    var allNameInputs = tbody.querySelectorAll('.member-name');
                    var nameCount = 0;
                    allNameInputs.forEach(function(inp) {
                        if (inp.value.trim().toLowerCase() === currentName) {
                            nameCount++;
                        }
                    });
                    isDuplicate = nameCount > 1;
                }
                
                if (isDuplicate) {
                    this.classList.add('is-invalid');
                    if (!this.nextElementSibling || !this.nextElementSibling.classList.contains('duplicate-warning')) {
                        var warning = document.createElement('div');
                        warning.className = 'invalid-feedback d-block duplicate-warning';
                        warning.textContent = 'This family member already exists in the list.';
                        this.parentNode.appendChild(warning);
                    }
                } else {
                    this.classList.remove('is-invalid');
                    var warning = this.parentNode.querySelector('.duplicate-warning');
                    if (warning) warning.remove();
                }
            });
        }

        if (photoCell) {
            photoCell.addEventListener('click', function(e) {
                if (e.target.closest('.member-photo-remove')) return;
                if (input) input.click();
            });
        }
        if (input) {
            input.addEventListener('change', function() {
                var file = this.files[0];
                if (!file) return;
                
                // Check for GIF files and reject them
                if (file.type === 'image/gif' || file.name.toLowerCase().endsWith('.gif')) {
                    alert('GIF files are not allowed. Please upload JPG or PNG images only.');
                    this.value = '';
                    if (placeholder) placeholder.classList.remove('d-none');
                    if (preview) preview.classList.add('d-none');
                    if (img) img.src = '';
                    return;
                }
                
                if (!file.type.match(/^image\//)) return;
                var reader = new FileReader();
                reader.onload = function(e) {
                    if (img) img.src = e.target.result;
                    if (placeholder) placeholder.classList.add('d-none');
                    if (preview) preview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            });
        }
        if (removePhotoBtn) {
            removePhotoBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (placeholder) placeholder.classList.remove('d-none');
                if (preview) preview.classList.add('d-none');
                if (img) img.src = '';
                if (input) input.value = '';
            });
        }
        if (removeRowBtn) {
            removeRowBtn.addEventListener('click', function() {
                var rows = tbody.querySelectorAll('.family-member-row');
                if (rows.length <= 1) return;
                row.remove();
                updateRowNumbers();
            });
        }
    }

    addBtn.addEventListener('click', addRow);

    // Apply date restrictions to Valid From fields
    function applyDateRestrictions() {
        var today = new Date().toISOString().split('T')[0];
        var validFromFields = document.querySelectorAll('.valid-from-field');
        validFromFields.forEach(function(field) {
            field.setAttribute('min', today);
            // Prevent user from clearing the value and selecting a past date
            field.addEventListener('change', function() {
                if (this.value && this.value < today) {
                    this.value = today;
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });
        });
    }
    
    // Apply date restrictions on page load
    applyDateRestrictions();
    
    // Reapply restrictions after adding new rows
    var originalAddRow = addRow;
    addRow = function() {
        originalAddRow();
        applyDateRestrictions();
    };

    // Group photo: preview and remove
    var groupZone = document.getElementById('groupPhotoUploadZone');
    var groupInput = document.getElementById('group_photo');
    var groupPlaceholder = document.getElementById('groupPhotoPlaceholder');
    var groupPreview = document.getElementById('groupPhotoPreview');
    var groupPreviewImg = document.getElementById('groupPhotoPreviewImg');
    var groupRemove = document.getElementById('groupPhotoRemove');
    if (groupZone && groupInput) {
        groupZone.addEventListener('click', function(e) {
            if (e.target.closest('.family-idcard-preview-remove')) return;
            groupInput.click();
        });
        groupZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = '#004a93';
            this.style.backgroundColor = '#eef4fc';
        });
        groupZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
        });
        groupZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            this.style.backgroundColor = '';
            var files = e.dataTransfer.files;
            if (files.length) {
                groupInput.files = files;
                var file = files[0];
                if (file && file.type.match(/^image\//)) {
                    var reader = new FileReader();
                    reader.onload = function(ev) {
                        if (groupPreviewImg) groupPreviewImg.src = ev.target.result;
                        if (groupPlaceholder) groupPlaceholder.classList.add('d-none');
                        if (groupPreview) groupPreview.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    }
    if (groupInput) {
        groupInput.addEventListener('change', function() {
            var file = this.files[0];
            if (!file || !file.type.match(/^image\//)) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                if (groupPreviewImg) groupPreviewImg.src = e.target.result;
                if (groupPlaceholder) groupPlaceholder.classList.add('d-none');
                if (groupPreview) groupPreview.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        });
    }
    if (groupRemove) {
        groupRemove.addEventListener('click', function(e) {
            e.stopPropagation();
            if (groupPlaceholder) groupPlaceholder.classList.remove('d-none');
            if (groupPreview) groupPreview.classList.add('d-none');
            if (groupPreviewImg) groupPreviewImg.src = '';
            if (groupInput) groupInput.value = '';
        });
    }

    // Initial bind for first row
    var firstRow = tbody.querySelector('.family-member-row');
    if (firstRow) bindRowEvents(firstRow);

    // Function to check for duplicate family members
    function checkDuplicateMembers() {
        var rows = tbody.querySelectorAll('.family-member-row');
        var names = [];
        var duplicates = [];
        
        rows.forEach(function(row) {
            var nameInput = row.querySelector('.member-name');
            if (nameInput && nameInput.value.trim()) {
                var name = nameInput.value.trim().toLowerCase();
                if (names.includes(name) && !duplicates.includes(name)) {
                    duplicates.push(name);
                }
                names.push(name);
            }
        });
        
        return duplicates;
    }

    // Function to display duplicate error
    function displayDuplicateError(duplicates) {
        // Remove any existing error
        var existingError = document.getElementById('duplicateMembersError');
        if (existingError) existingError.remove();
        
        if (duplicates.length > 0) {
            var errorMsg = 'Duplicate family member(s) found: ' + duplicates.map(function(n) {
                return n.charAt(0).toUpperCase() + n.slice(1);
            }).join(', ') + '. Please ensure each family member is added only once.';
            
            var errorDiv = document.createElement('div');
            errorDiv.id = 'duplicateMembersError';
            errorDiv.className = 'alert alert-danger alert-dismissible fade show mt-3';
            errorDiv.innerHTML = '<button type="button" class="btn-close" data-bs-dismiss="alert"></button><strong>Validation Error - </strong>' + errorMsg;
            
            var form = document.getElementById('familyIdcardForm');
            form.insertBefore(errorDiv, form.firstChild);
            
            // Scroll to error
            errorDiv.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // Form submit: ensure at least one member and no duplicates
    document.getElementById('familyIdcardForm').addEventListener('submit', function(e) {
        var rows = tbody.querySelectorAll('.family-member-row');
        if (rows.length === 0) {
            e.preventDefault();
            document.getElementById('noMembersMsg').classList.remove('d-none');
            return;
        }
        
        // Check for duplicates
        var duplicates = checkDuplicateMembers();
        if (duplicates.length > 0) {
            e.preventDefault();
            displayDuplicateError(duplicates);
            return;
        }
        
        var groupInput = document.getElementById('group_photo');
        if (groupInput && (!groupInput.files || !groupInput.files.length)) {
            e.preventDefault();
            groupInput.reportValidity();
        }
    });
})();

(function() {
    window.addEventListener('load', function() {
        var form = document.getElementById('familyIdcardForm');
        if (form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        }
    });
})();
</script>
@endsection
