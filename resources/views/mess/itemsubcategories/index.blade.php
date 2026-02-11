@extends('admin.layouts.master')
@section('title', 'Item Sub Category Master')
@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Item Sub Category Master"  />
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Item Sub Category Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemSubcategoryModal">
                    Add Item Sub Category
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
                            <th class="col">Item Name</th>
                            <th class="col">Item Code</th>
                            <th class="col">Unit Measurement</th>
                            <th class="col">Standard Cost</th>
                            <th class="col">Status</th>
                            <th class="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($itemsubcategories as $itemsubcategory)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $itemsubcategory->category->category_name ?? '-' }}</td>
                                <td>{{ $itemsubcategory->item_name }}</td>
                                <td>{{ $itemsubcategory->item_code ?? '-' }}</td>
                                <td>{{ $itemsubcategory->unit_measurement ?? '-' }}</td>
                                <td>
                                    @if($itemsubcategory->standard_cost)
                                        ₹{{ number_format($itemsubcategory->standard_cost, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <span class="badge text-white bg-{{ $itemsubcategory->status_badge_class }}">
                                        {{ $itemsubcategory->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-itemsubcategory"
                                                data-id="{{ $itemsubcategory->id }}"
                                                data-category-id="{{ $itemsubcategory->category_id }}"
                                                data-item-name="{{ e($itemsubcategory->item_name) }}"
                                                data-item-code="{{ e($itemsubcategory->item_code ?? '') }}"
                                                data-unit-measurement="{{ e($itemsubcategory->unit_measurement ?? '') }}"
                                                data-standard-cost="{{ $itemsubcategory->standard_cost ?? '' }}"
                                                data-description="{{ e($itemsubcategory->description ?? '') }}"
                                                data-status="{{ e($itemsubcategory->status ?? 'active') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.itemsubcategories.destroy', $itemsubcategory->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this item sub category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" style="display: none;">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">No item sub categories found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Create Item Subcategory Modal --}}
<div class="modal fade" id="createItemSubcategoryModal" tabindex="-1" aria-labelledby="createItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.itemsubcategories.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createItemSubcategoryModalLabel">Add Item Sub Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>{{ $category->category_name }}</option>
                                @endforeach
                            </select>
                            @error('category_id')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" class="form-control" required value="{{ old('item_name') }}">
                            @error('item_name')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control bg-light" value="" readonly placeholder="Auto-generated on save">
                            <small class="text-muted">Mandatory. Auto-generated.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Measurement</label>
                            <input type="text" name="unit_measurement" class="form-control" value="{{ old('unit_measurement') }}" placeholder="e.g., kg, liter, piece">
                            @error('unit_measurement')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Standard Cost</label>
                            <input type="number" name="standard_cost" class="form-control" step="0.01" min="0" value="{{ old('standard_cost') }}" placeholder="0.00">
                            @error('standard_cost')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                            @error('description')<div class="text-danger small">{{ $message }}</div>@enderror
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

{{-- Edit Item Subcategory Modal --}}
<div class="modal fade" id="editItemSubcategoryModal" tabindex="-1" aria-labelledby="editItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="editItemSubcategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editItemSubcategoryModalLabel">Edit Item Sub Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <select name="category_id" id="edit_category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->category_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="item_name" id="edit_item_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Item Code <span class="text-danger">*</span></label>
                            <input type="text" id="edit_item_code_display" class="form-control bg-light" readonly>
                            <small class="text-muted">Mandatory. Auto-generated; read-only.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Unit Measurement</label>
                            <input type="text" name="unit_measurement" id="edit_unit_measurement" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Standard Cost</label>
                            <input type="number" name="standard_cost" id="edit_standard_cost" class="form-control" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="edit_status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
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
    document.querySelectorAll('.btn-edit-itemsubcategory').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var id = this.getAttribute('data-id');
            var baseUrl = '{{ url("admin/mess/itemsubcategories") }}';
            document.getElementById('editItemSubcategoryForm').action = baseUrl + '/' + id;
            document.getElementById('edit_category_id').value = this.getAttribute('data-category-id') || '';
            document.getElementById('edit_item_name').value = this.getAttribute('data-item-name') || '';
            document.getElementById('edit_item_code_display').value = this.getAttribute('data-item-code') || '-';
            document.getElementById('edit_unit_measurement').value = this.getAttribute('data-unit-measurement') || '';
            document.getElementById('edit_standard_cost').value = this.getAttribute('data-standard-cost') || '';
            document.getElementById('edit_description').value = this.getAttribute('data-description') || '';
            document.getElementById('edit_status').value = this.getAttribute('data-status') || 'active';
            new bootstrap.Modal(document.getElementById('editItemSubcategoryModal')).show();
        });
    });
});
</script>
@endpush
@endsection
