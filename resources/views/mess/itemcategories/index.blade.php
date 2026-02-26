@extends('admin.layouts.master')
@section('title', 'Category Item Master')
@section('setup_content')
@php
    $categoryTypes = \App\Models\Mess\ItemCategory::categoryTypes();
@endphp
<div class="container-fluid">
    <div class="datatables">
        <div class="card">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Category Item Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemCategoryModal">
                    Add Category Item
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="itemCategoriesTable" class="table table-striped table-hover table-bordered align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th style="width: 70px; background-color: #004a93; color: #fff; border-color: #004a93;">#</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Category Name</th>
                            <th style="width: 160px; background-color: #004a93; color: #fff; border-color: #004a93;">Category Type</th>
                            <th style="background-color: #004a93; color: #fff; border-color: #004a93;">Item Category Description</th>
                            <th style="width: 120px; background-color: #004a93; color: #fff; border-color: #004a93;">Status</th>
                            <th style="width: 160px; background-color: #004a93; color: #fff; border-color: #004a93;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($itemcategories as $itemcategory)
                            <tr>
                                <td>{{ $itemcategory->id }}</td>
                                <td><div class="fw-semibold">{{ $itemcategory->category_name }}</div></td>
                                <td>
                                    {{ $categoryTypes[$itemcategory->category_type ?? 'raw_material'] ?? ucfirst(str_replace('_', ' ', $itemcategory->category_type ?? '')) }}
                                </td>
                                <td>{{ $itemcategory->description ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $itemcategory->status_badge_class }}">
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
                                              onsubmit="return confirm('Are you sure you want to delete this category item?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
</div>

{{-- Create Category Item Modal --}}
<div class="modal fade" id="createItemCategoryModal" tabindex="-1" aria-labelledby="createItemCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.itemcategories.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createItemCategoryModalLabel">Add Category Item</h5>
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
                            <select name="category_type" class="form-select select2" required>
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
                            <select name="status" class="form-select select2">
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

{{-- Edit Category Item Modal --}}
<div class="modal fade" id="editItemCategoryModal" tabindex="-1" aria-labelledby="editItemCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="editItemCategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editItemCategoryModalLabel">Edit Category Item</h5>
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
                            <select name="category_type" id="edit_category_type" class="form-select select2" required>
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
                            <select name="status" id="edit_status" class="form-select select2">
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

@include('components.mess-master-datatables', ['tableId' => 'itemCategoriesTable', 'searchPlaceholder' => 'Search category items...', 'orderColumn' => 1, 'actionColumnIndex' => 5, 'infoLabel' => 'category items'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-itemcategory');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editItemCategoryForm').action = '{{ url("admin/mess/itemcategories") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_category_name').value = btn.getAttribute('data-category-name') || '';
        document.getElementById('edit_category_type').value = btn.getAttribute('data-category-type') || '';
        document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editItemCategoryModal')).show();
    }, true);
});
</script>
@endpush

<style>
.table thead th { background-color: #004a93 !important; color: #fff !important; }
</style>
@endsection
