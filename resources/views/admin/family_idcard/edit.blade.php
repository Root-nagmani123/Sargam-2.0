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
                            <option value="Employee" {{ old('card_type', $request->card_type) == 'Employee' ? 'selected' : '' }}>Employee</option>
                        </select>
                        @error('card_type')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $request->name) }}" placeholder="Enter Family Member Name" required>
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="relation" class="form-label">Relation</label>
                        <select name="relation" id="relation" class="form-select">
                            <option value="">Select Relation</option>
                            @foreach(['Spouse', 'Son', 'Daughter', 'Father', 'Mother', 'Brother', 'Sister', 'Other'] as $opt)
                                <option value="{{ $opt }}" {{ old('relation', $request->relation) == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                        @error('relation')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
                        <input type="text" name="section" id="section" class="form-control" value="{{ old('section', $request->section) }}" placeholder="Enter Section" required>
                        @error('section')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="family_member_id" class="form-label">Family Member ID</label>
                        <input type="text" name="family_member_id" id="family_member_id" class="form-control" value="{{ old('family_member_id', $request->family_member_id) }}" placeholder="Issued ID">
                        @error('family_member_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" name="dob" id="dob" class="form-control" value="{{ old('dob', $request->dob ? $request->dob->format('Y-m-d') : '') }}">
                        @error('dob')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="valid_from" class="form-label">Valid From</label>
                        <input type="date" name="valid_from" id="valid_from" class="form-control valid-from-field" value="{{ old('valid_from', $request->valid_from ? $request->valid_from->format('Y-m-d') : '') }}" min="{{ date('Y-m-d') }}">
                        @error('valid_from')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="valid_to" class="form-label">Valid To</label>
                        <input type="date" name="valid_to" id="valid_to" class="form-control" value="{{ old('valid_to', $request->valid_to ? $request->valid_to->format('Y-m-d') : '') }}">
                        @error('valid_to')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Upload Family Photo</label>
                        <div class="family-idcard-upload-zone position-relative" id="familyPhotoUploadZone">
                            <input type="file" name="family_photo" id="family_photo" class="d-none" accept="image/*">
                            <div class="family-idcard-upload-placeholder" id="familyPhotoPlaceholder">
                                @if($request->family_photo)
                                    <img src="{{ asset('storage/' . $request->family_photo) }}" alt="Current" class="family-idcard-preview-img mb-2" style="max-height:120px;">
                                    <p class="mb-0 small text-muted">Click to change or drag and drop</p>
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

                <h6 class="fw-semibold mt-4 mb-3">Family Members List</h6>
                <div class="table-responsive">
                    <table class="table table-bordered family-idcard-members-table mb-0">
                        <thead>
                            <tr>
                                <th>S.No.</th>
                                <th>Family Member ID</th>
                                <th>Name</th>
                                <th>Relation</th>
                                <th>DOB</th>
                                <th>Valid From - To</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($existingFamilyMembers as $idx => $member)
                                <tr>
                                    <td class="fw-medium">{{ $idx + 1 }}</td>
                                    <td>{{ $member->family_member_id ?? '--' }}</td>
                                    <td>{{ $member->name ?? '--' }}</td>
                                    <td>{{ $member->relation ?? '--' }}</td>
                                    <td>{{ $member->dob ? $member->dob->format('d/m/Y') : '--' }}</td>
                                    <td>
                                        @if($member->valid_from && $member->valid_to)
                                            {{ $member->valid_from->format('d/m/Y') }} - {{ $member->valid_to->format('d/m/Y') }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">No other family members.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <p class="small text-danger mt-4 mb-0">*Required Fields: All marked fields are mandatory.</p>

                <div class="d-flex gap-2 justify-content-end mt-4 pt-3 border-top">
                    <a href="{{ route('admin.family_idcard.index') }}" class="btn btn-outline-primary px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary px-4">Update</button>
                </div>
            </div>
        </div>
    </form>
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
        if (!file || !file.type.match(/^image\//)) return;
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

// Apply date restrictions to Valid From field
(function() {
    var today = new Date().toISOString().split('T')[0];
    var validFromField = document.querySelector('.valid-from-field');
    if (validFromField) {
        validFromField.setAttribute('min', today);
        validFromField.addEventListener('change', function() {
            if (this.value && this.value < today) {
                this.value = today;
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
})();
</script>
@endsection
