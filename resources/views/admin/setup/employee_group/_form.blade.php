@php($isEdit = !empty($employeeGroup))
<form method="POST" action="{{ $isEdit ? route('admin.setup.employee_group.update', encrypt($employeeGroup->pk)) : route('admin.setup.employee_group.store') }}" id="employeeGroupForm">
    @csrf
    <div class="mb-3">
        <label class="form-label fw-semibold">Employee Group Name<span class="text-danger">*</span></label>
        <input type="text" name="employee_group_name" class="form-control" placeholder="Enter name" value="{{ old('employee_group_name', $employeeGroup->emp_group_name ?? '') }}" required>
        @error('employee_group_name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
    </div>
</form>