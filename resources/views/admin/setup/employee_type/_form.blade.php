@php($isEdit = !empty($employeeType))
<form method="POST" action="{{ $isEdit ? route('admin.setup.employee_type.update', encrypt($employeeType->pk)) : route('admin.setup.employee_type.store') }}" id="employeeTypeForm">
    @csrf
    <div class="mb-3">
        <label class="form-label fw-semibold">Employee Type Name<span class="text-danger">*</span></label>
        <input type="text" name="employee_type_name" class="form-control" placeholder="Enter name" value="{{ old('employee_type_name', $employeeType->category_type_name ?? '') }}" required>
        @error('employee_type_name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
    </div>
</form>