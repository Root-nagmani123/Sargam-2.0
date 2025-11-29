@php($isEdit = !empty($department))
<form method="POST" action="{{ $isEdit ? route('admin.setup.department_master.update', encrypt($department->pk)) : route('admin.setup.department_master.store') }}" id="departmentMasterForm">
    @csrf
    <div class="mb-3">
        <label class="form-label fw-semibold">Department Name <span class="text-danger">*</span></label>
        <input type="text" name="department_name" class="form-control" placeholder="Enter department name" value="{{ old('department_name', $department->department_name ?? '') }}" required>
        @error('department_name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
    </div>
</form>