@extends('admin.layouts.master')
@section('title', 'Edit Family ID Card - Sargam')
@section('setup_content')
<div class="container-fluid family-idcard-create-page">
    <x-breadcrum title="Edit Family ID Card Request"></x-breadcrum>

    <form action="{{ route('admin.family_idcard.update', $request->id) }}" method="POST" enctype="multipart/form-data" class="needs-validation" id="familyIdcardForm" novalidate>
        @csrf
        @method('PUT')

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-semibold mb-4">Please add the Request For Family Member ID Card.</h6>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="employee_id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                        <input type="text" name="employee_id" id="employee_id" class="form-control" value="{{ old('employee_id', $request->employee_id) }}" placeholder="Enter your ID Card No." required>
                        @error('employee_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                        <input type="text" name="designation" id="designation" class="form-control" value="{{ old('designation', $request->designation) }}" placeholder="Enter Designation." required>
                        @error('designation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="card_type" class="form-label">Card Type <span class="text-danger">*</span></label>
                        <select name="card_type" id="card_type" class="form-select" required>
                            <option value="">Select The Card Type</option>
                            <option value="Family" {{ old('card_type', $request->card_type) == 'Family' ? 'selected' : '' }}>Family</option>
                             </select>
                        @error('card_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    @if($employeeType === 'Contractual Employee')
                    <div class="col-md-6">
                        <label for="approval_authority" class="form-label">Approval Authority <span class="text-danger">*</span></label>
                        <select name="approval_authority" id="approval_authority" class="form-select">
                            <option value="">-- Select Authority --</option>
                            @foreach($approvalAuthorityEmployees ?? [] as $emp)
                                @php $empName = trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')); @endphp
                                <option value="{{ $emp->pk }}" {{ $currentApprovalAuthorityPk == $emp->pk ? 'selected' : '' }}>{{ $empName }}{{ $emp->designation ? ' (' . $emp->designation->designation_name . ')' : '' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Approval authority on behalf of your section</small>
                        @error('approval_authority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" name="section" id="section" class="form-control" value="{{ old('section', $request->section) }}" placeholder="Enter Section" required>
                        @error('section')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    {{-- Hidden fields to preserve main-row details (handled via members list visually) --}}
                    <input type="hidden" name="name" value="{{ old('name', $request->name) }}">
                    <input type="hidden" name="relation" value="{{ old('relation', $request->relation) }}">
                    <input type="hidden" name="family_member_id" value="{{ old('family_member_id', $request->family_member_id) }}">
                    <input type="hidden" name="dob" value="{{ old('dob', $request->dob ? $request->dob->format('Y-m-d') : '') }}">
                    <input type="hidden" name="valid_from" value="{{ old('valid_from', $request->valid_from ? $request->valid_from->format('Y-m-d') : '') }}">
                    <input type="hidden" name="valid_to" value="{{ old('valid_to', $request->valid_to ? $request->valid_to->format('Y-m-d') : '') }}">
                    <div class="col-12">
                        <label class="form-label">Upload Family Photo</label>
                        @php
                            $familyPhotoPath = $request->family_photo;
                            $familyPhotoExists = $familyPhotoPath && \Storage::disk('public')->exists($familyPhotoPath);
                        @endphp
                        <div class="family-idcard-upload-zone position-relative" id="familyPhotoUploadZone">
                            <input type="file" name="family_photo" id="family_photo" class="d-none" accept=".jpeg,.jpg,.png">
                            <div class="family-idcard-upload-placeholder" id="familyPhotoPlaceholder">
                                @if($familyPhotoExists)
                                    <img src="{{ asset('storage/' . $familyPhotoPath) }}" alt="Current" class="family-idcard-preview-img mb-2" style="max-height:120px;">
                                    <p class="mb-0 small text-muted">Click to change or drag and drop</p>
                                @elseif($familyPhotoPath)
                                    <p class="mb-0 small text-muted text-warning">No file available in storage</p>
                                    <p class="mt-1 mb-0 small text-muted">Click to upload or drag and drop</p>
                                @else
                                    <i class="material-icons material-symbols-rounded family-idcard-upload-icon">upload</i>
                                    <p class="mt-2 mb-0">Click to upload or drag and drop</p>
                                @endif
                            </div>
                            <div class="family-idcard-upload-preview d-none position-relative" id="familyPhotoPreview">
                                <img src="" alt="Preview" class="family-idcard-preview-img" id="familyPhotoPreviewImg">
                                <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 family-idcard-preview-remove" id="familyPhotoRemove" aria-label="Remove photo">
                                    <i class="material-icons material-symbols-rounded" style="font-size:18px;">close</i>
                                </button>
                            </div>
                        </div>
                        @error('family_photo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <!-- Family Members List (create-style: inline editable rows) -->
                <div class="d-flex justify-content-between align-items-center mt-4 mb-3">
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
                                <th>Individual Photo</th>
                                <th style="width: 70px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="familyMembersBody">
                            @php $relationOptions = ['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Brother', 'Sister', 'Other']; @endphp
                            @forelse($existingFamilyMembers as $idx => $member)
                                <tr class="family-member-row" data-row-index="{{ $idx }}">
                                    <td class="align-middle fw-medium row-sno">{{ $idx + 1 }}</td>
                                    <td class="align-middle">
                                        <input type="hidden" name="members[{{ $idx }}][id]" value="{{ $member->id }}">
                                        <input type="text" name="members[{{ $idx }}][name]" class="form-control  member-name" value="{{ old('members.'.$idx.'.name', $member->name ?? '') }}" placeholder="Name" required>
                                    </td>
                                    <td class="align-middle">
                                        <select name="members[{{ $idx }}][relation]" class="form-select form-select-sm">
                                            <option value="">Select Relation</option>
                                            @foreach($relationOptions as $opt)
                                                <option value="{{ $opt }}" {{ old('members.'.$idx.'.relation', $member->relation ?? '') == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="align-middle">
                                        <input type="date" name="members[{{ $idx }}][dob]" class="form-control " value="{{ old('members.'.$idx.'.dob', $member->dob ? $member->dob->format('Y-m-d') : '') }}">
                                    </td>
                                    <td class="align-middle">
                                        <input type="date" name="members[{{ $idx }}][valid_from]" class="form-control  valid-from-field" min="{{ date('Y-m-d') }}" value="{{ old('members.'.$idx.'.valid_from', $member->valid_from ? $member->valid_from->format('Y-m-d') : '') }}">
                                    </td>
                                    <td class="align-middle">
                                        <input type="date" name="members[{{ $idx }}][valid_to]" class="form-control " value="{{ old('members.'.$idx.'.valid_to', $member->valid_to ? $member->valid_to->format('Y-m-d') : '') }}">
                                    </td>
                                    <td class="align-middle">
                                    <td class="align-middle">
                                        @php
                                            $memberPhoto = $member->id_photo_path ?? $member->family_photo ?? null;
                                            $memberPhotoExists = $memberPhoto && \Storage::disk('public')->exists($memberPhoto);
                                        @endphp
                                        <div class="family-idcard-upload-zone-sm position-relative member-photo-cell" data-row="{{ $idx }}">
                                            <input type="file" name="members[{{ $idx }}][family_photo]" class="d-none member-photo-input" accept=".jpeg,.jpg,.png" data-row="{{ $idx }}">
                                            <div class="family-idcard-upload-placeholder-sm" data-placeholder="{{ $idx }}">
                                                @if($memberPhotoExists)
                                                    <img src="{{ asset('storage/' . $memberPhoto) }}" alt="" class="member-preview-img" data-img="{{ $idx }}" style="max-height:60px; border-radius:4px;">
                                                    <span class="small d-block mt-1 text-muted">Click to change</span>
                                                @elseif($memberPhoto)
                                                    <span class="small d-block mt-1 text-warning">No file available in storage</span>
                                                    <span class="small text-muted d-block">Click to upload</span>
                                                @else
                                                    <i class="material-icons material-symbols-rounded" style="font-size:1.5rem; color:#6c757d;">upload</i>
                                                    <span class="small d-block mt-1">Upload</span>
                                                    <span class="small text-muted d-block">JPG, PNG. Max 2 MB</span>
                                                @endif
                                            </div>
                                            <div class="family-idcard-upload-preview-sm d-none position-relative" data-preview="{{ $idx }}">
                                                <img src="" alt="Preview" class="member-preview-img" data-img="{{ $idx }}" style="max-height:60px; border-radius:4px;">
                                                <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 m-0 p-0 member-photo-remove" style="width:22px; height:22px; font-size:14px; line-height:1; border-radius:50%;" data-row="{{ $idx }}" aria-label="Remove">&times;</button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="align-middle text-center">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-member-btn" data-row="{{ $idx }}" title="Remove row" aria-label="Remove row">
                                            <i class="material-icons material-symbols-rounded" style="font-size:18px;">delete</i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr id="noMembersRow">
                                    <td colspan="8" class="text-center text-muted py-3">No other family members. Click &quot;Add Family Member&quot; to add one.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p id="noMembersMsg" class="small text-muted mt-2 d-none">Add at least one family member using the button above.</p>

                <template id="familyMemberRowTemplate">
@verbatim
                    <tr class="family-member-row" data-row-index="{{INDEX}}">
                        <td class="align-middle fw-medium row-sno">{{SNO}}</td>
                        <td class="align-middle">
                            <input type="text" name="members[{{INDEX}}][name]" class="form-control  member-name" placeholder="Name" required>
                        </td>
                        <td class="align-middle">
                            <select name="members[{{INDEX}}][relation]" class="form-select form-select-sm">
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
                            <input type="date" name="members[{{INDEX}}][dob]" class="form-control ">
                        </td>
                        <td class="align-middle">
                            <input type="date" name="members[{{INDEX}}][valid_from]" class="form-control  valid-from-field" min="{{ date('Y-m-d') }}">
                        </td>
                        <td class="align-middle">
                            <input type="date" name="members[{{INDEX}}][valid_to]" class="form-control ">
                        </td>
                        <td class="align-middle">
                            <div class="family-idcard-upload-zone-sm position-relative member-photo-cell" data-row="{{INDEX}}">
                                <input type="file" name="members[{{INDEX}}][family_photo]" class="d-none member-photo-input" accept=".jpeg,.jpg,.png" data-row="{{INDEX}}">
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

                <p class="small text-danger mt-4 mb-0">*Required Fields: All marked fields are mandatory.</p>

                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-outline-primary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Update</button>
                </div>
            </div>
        </div>
    </form>

    {{-- Template for new family member rows (same layout as above rows) --}}
    <template id="familyMemberRowTemplate">
@verbatim
        <tr class="family-member-row" data-row-index="{{INDEX}}">
            <td class="align-middle fw-medium row-sno">{{SNO}}</td>
            <td class="align-middle">
                <input type="hidden" name="members[{{INDEX}}][id]" value="">
                <input type="text" name="members[{{INDEX}}][name]" class="form-control  member-name" placeholder="Name" required>
            </td>
            <td class="align-middle">
                <select name="members[{{INDEX}}][relation]" class="form-select form-select-sm">
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
                <input type="date" name="members[{{INDEX}}][dob]" class="form-control ">
            </td>
            <td class="align-middle">
                <input type="date" name="members[{{INDEX}}][valid_from]" class="form-control  valid-from-field">
            </td>
            <td class="align-middle">
                <input type="date" name="members[{{INDEX}}][valid_to]" class="form-control ">
            </td>
            <td class="align-middle">
                <div class="family-idcard-upload-zone-sm position-relative member-photo-cell" data-row="{{INDEX}}">
                    <input type="file" name="members[{{INDEX}}][family_photo]" class="d-none member-photo-input" accept=".jpeg,.jpg,.png" data-row="{{INDEX}}">
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
</div>

<style>
.family-idcard-create-page .form-control,
.family-idcard-create-page .form-select { border-radius: 0.375rem; border: 1px solid #ced4da; }
.family-idcard-upload-zone {
    background-color: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 2rem 1rem;
    text-align: center;
    cursor: pointer;
    min-height: 160px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.family-idcard-upload-zone:hover { background-color: #eef4fc; border-color: #004a93; }
.family-idcard-upload-icon { font-size: 2.5rem !important; color: #6c757d; }
.family-idcard-upload-preview { display: flex; align-items: center; justify-content: center; min-height: 160px; padding: 0.5rem; }
.family-idcard-preview-img { max-width: 100%; max-height: 180px; object-fit: contain; border-radius: 0.375rem; }
.family-idcard-preview-remove { width: 32px; height: 32px; padding: 0; z-index: 2; border-radius: 50%; }
.family-idcard-members-table thead tr { background: #122442; color: #fff; }
.family-idcard-members-table thead th { font-weight: 600; padding: 0.75rem 1rem; }
.btn-outline-primary { border: 1px solid #004a93; color: #004a93; }
.btn-outline-primary:hover { background-color: #004a93; color: #fff; }
</style>

<script>
(function() {
    'use strict';
    var zone = document.getElementById('familyPhotoUploadZone');
    var input = document.getElementById('family_photo');
    var placeholder = document.getElementById('familyPhotoPlaceholder');
    var preview = document.getElementById('familyPhotoPreview');
    var previewImg = document.getElementById('familyPhotoPreviewImg');
    var removeBtn = document.getElementById('familyPhotoRemove');

    function showPreview(file) {
        if (!file) return;
        
        // Check for GIF files and reject them
        if (file.type === 'image/gif' || file.name.toLowerCase().endsWith('.gif')) {
            alert('GIF files are not allowed. Please upload JPG or PNG images only.');
            input.value = '';
            clearPreview();
            return;
        }
        
        if (!file.type.match(/^image\//)) return;
        var reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            placeholder.classList.add('d-none');
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
    function clearPreview() {
        previewImg.src = '';
        placeholder.classList.remove('d-none');
        preview.classList.add('d-none');
        if (input) input.value = '';
    }
    if (zone) {
        zone.addEventListener('click', function(e) {
            if (e.target.closest('.family-idcard-preview-remove')) return;
            if (input) input.click();
        });
        zone.addEventListener('dragover', function(e) { e.preventDefault(); this.style.borderColor = '#004a93'; });
        zone.addEventListener('dragleave', function(e) { e.preventDefault(); this.style.borderColor = ''; });
        zone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = '';
            var files = e.dataTransfer.files;
            if (files.length && input) { input.files = files; showPreview(files[0]); }
        });
    }
    if (input) input.addEventListener('change', function() { showPreview(this.files[0]); });
    if (removeBtn) removeBtn.addEventListener('click', function(e) { e.stopPropagation(); clearPreview(); });
})();

// Family members table: add/remove rows, photo preview, and date restrictions
(function() {
    var tbody = document.getElementById('familyMembersBody');
    var addBtn = document.getElementById('addFamilyMemberBtn');
    var template = document.getElementById('familyMemberRowTemplate');
    if (!tbody || !addBtn || !template) return;

    var rowIndex = tbody.querySelectorAll('.family-member-row').length || 0;

    function applyDateRestrictions() {
        var today = new Date().toISOString().split('T')[0];
        var validFromFields = tbody.querySelectorAll('.valid-from-field');
        validFromFields.forEach(function(field) {
            field.setAttribute('min', today);
        });
    }

    function updateRowNumbers() {
        var rows = tbody.querySelectorAll('.family-member-row');
        rows.forEach(function(row, i) {
            row.setAttribute('data-row-index', i);
            var sno = row.querySelector('.row-sno');
            if (sno) sno.textContent = i + 1;

            // Update name attributes to use new index
            row.querySelectorAll('[name]').forEach(function(inp) {
                var name = inp.getAttribute('name');
                if (!name) return;
                name = name.replace(/members\[\d+\]/, 'members[' + i + ']');
                inp.setAttribute('name', name);
            });

            // Update data-row / placeholder / preview indices
            row.querySelectorAll('.member-photo-input, .member-photo-cell, [data-placeholder], [data-preview], .member-preview-img, .member-photo-remove, .remove-member-btn').forEach(function(el) {
                if (el.hasAttribute('data-row')) el.setAttribute('data-row', i);
                if (el.getAttribute('data-placeholder') !== null) el.setAttribute('data-placeholder', i);
                if (el.getAttribute('data-preview') !== null) el.setAttribute('data-preview', i);
                if (el.getAttribute('data-img') !== null) el.setAttribute('data-img', i);
            });
        });
    }

    function bindRowEvents(row) {
        if (!row) return;
        var idx = row.getAttribute('data-row-index');
        var photoCell = row.querySelector('.member-photo-cell');
        var input = row.querySelector('.member-photo-input');
        var placeholder = row.querySelector('[data-placeholder="' + idx + '"]');
        var preview = row.querySelector('[data-preview="' + idx + '"]');
        // Always use the img that lives inside the preview container for live previews
        var img = preview ? preview.querySelector('.member-preview-img[data-img="' + idx + '"]') : null;
        var removePhotoBtn = row.querySelector('.member-photo-remove');
        var removeRowBtn = row.querySelector('.remove-member-btn');

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
                if (file.type === 'image/gif' || file.name.toLowerCase().endsWith('.gif')) {
                    alert('GIF files are not allowed. Please upload JPG or PNG images only.');
                    this.value = '';
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
                if (rows.length <= 1) {
                    alert('At least one family member is required.');
                    return;
                }
                row.remove();
                updateRowNumbers();
                applyDateRestrictions();
            });
        }
    }

    function addRow() {
        var html = template.innerHTML
            .replace(/\{\{INDEX\}\}/g, rowIndex)
            .replace(/\{\{SNO\}\}/g, rowIndex + 1);
        tbody.insertAdjacentHTML('beforeend', html);
        var newRow = tbody.querySelector('.family-member-row:last-child');
        rowIndex++;
        updateRowNumbers();
        bindRowEvents(newRow);
        applyDateRestrictions();
        var noRow = document.getElementById('noMembersRow');
        if (noRow) noRow.remove();
    }

    // Initial bindings for existing rows
    tbody.querySelectorAll('.family-member-row').forEach(function(row) {
        bindRowEvents(row);
    });
    applyDateRestrictions();

    addBtn.addEventListener('click', function() {
        addRow();
    });
})();
</script>
@endsection
