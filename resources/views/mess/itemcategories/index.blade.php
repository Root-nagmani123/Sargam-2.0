@extends('admin.layouts.master')
@section('title', 'Item Category Master')
@section('setup_content')
@php
    $categoryTypes = \App\Models\Mess\ItemCategory::categoryTypes();
@endphp
<div class="container-fluid">
    <x-breadcrum title="Item Category Master"  />
    <div class="card" style="border-left: 4px solid #004a93;">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Item Category Master</h4>
                <button type="button" class="btn btn-primary px-4 py-2 rounded-1 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createItemCategoryModal">
                    <iconify-icon icon="ep:circle-plus-filled"></iconify-icon> Add Item Category
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th class="col">#</th>
                            <th class="col">Category Name</th>
                            <th class="col">Category Type</th>
                            <th class="col">Item Category Description</th>
                            <th class="col">Status</th>
                            <th class="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itemcategories as $itemcategory)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $itemcategory->category_name }}</td>
                                <td>
                                    {{ $categoryTypes[$itemcategory->category_type ?? 'raw_material'] ?? ucfirst(str_replace('_', ' ', $itemcategory->category_type ?? '')) }}
                                </td>
                                <td>{{ $itemcategory->description ?? '-' }}</td>
                                <td>
                                    <span class="badge text-white bg-{{ $itemcategory->status_badge_class }}">
                                        {{ $itemcategory->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-itemcategory"
                                                data-id="{{ $itemcategory->id }}"
                                                data-category-name="{{ e($itemcategory->category_name) }}"
                                                data-category-type="{{ e($itemcategory->category_type ?? 'raw_material') }}"
                                                data-description="{{ e($itemcategory->description ?? '') }}"
                                                data-status="{{ e($itemcategory->status ?? 'active') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.itemcategories.destroy', $itemcategory->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this item category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No item categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Item Category Modal --}}
<div class="modal fade" id="createItemCategoryModal" tabindex="-1" aria-labelledby="createItemCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.itemcategories.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createItemCategoryModalLabel">Add Item Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" class="form-control" required value="{{ old('category_name') }}">
                            @error('category_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="category_type" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($categoryTypes as $value => $label)
                                    <option value="{{ $value }}" {{ old('category_type') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('category_type')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Item Category Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <div class="text-muted small">Default is Active.</div>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Item Category Modal --}}
<div class="modal fade" id="editItemCategoryModal" tabindex="-1" aria-labelledby="editItemCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editItemCategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editItemCategoryModalLabel">Edit Item Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="category_name" id="edit_category_name" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Category Type <span class="text-danger">*</span></label>
                            <select name="category_type" id="edit_category_type" class="form-select" required>
                                <option value="">Select</option>
                                @foreach($categoryTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Item Category Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-edit-itemcategory').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var baseUrl = '{{ url("admin/mess/itemcategories") }}';
            document.getElementById('editItemCategoryForm').action = baseUrl + '/' + id;
            document.getElementById('edit_category_name').value = this.getAttribute('data-category-name') || '';
            document.getElementById('edit_category_type').value = this.getAttribute('data-category-type') || '';
            document.getElementById('edit_description').value = this.getAttribute('data-description') || '';
            document.getElementById('edit_status').value = this.getAttribute('data-status') || 'active';
            new bootstrap.Modal(document.getElementById('editItemCategoryModal')).show();
        });
    });
});
</script>
@endpush
@endsection
