@php($isEdit = !empty($caste))
<form method="POST" action="{{ $isEdit ? route('admin.setup.caste_category.update', encrypt($caste->pk)) : route('admin.setup.caste_category.store') }}" id="casteCategoryForm">
    @csrf
    <div class="mb-3">
        <label class="form-label fw-semibold">Caste Category Name <span class="text-danger">*</span></label>
        <input type="text" name="category_name" class="form-control" placeholder="Enter caste category name" value="{{ old('category_name', $caste->category_name ?? '') }}" required>
        @error('category_name')
            <small class="text-danger">{{ $message }}</small>
        @enderror
    </div>
    <div class="d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Update' : 'Save' }}</button>
    </div>
</form>