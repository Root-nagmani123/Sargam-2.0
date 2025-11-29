@php($isEdit = !empty($designation))
<form method="POST" action="{{ $isEdit ? route('admin.setup.designation_master.update', encrypt($designation->pk)) : route('admin.setup.designation_master.store') }}" id="designationMasterForm">
    @csrf
    <div class="mb-3">
        <label class="form-label fw-semibold">Designation Name <span class="text-danger">*</span></label>
        <input type="text" name="designation_name" class="form-control" placeholder="Enter designation name" value="{{ old('designation_name', $designation->designation_name ?? '') }}" required>
        @error('designation_name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
    </div>
</form>