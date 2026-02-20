@extends('admin.layouts.master')
@section('title', 'Item Master')
@section('setup_content')
<div class="container-fluid">
    <div class="datatables">
        <div class="card">
            <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Item Master</h4>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemSubcategoryModal">
                    Add Item
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="table-responsive">
                <table id="itemSubcategoriesTable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff; border-color: #af2910;">#</th>
                            <th style="background-color: #af2910; color: #fff; border-color: #af2910;">Item Name</th>
                            <th style="width: 140px; background-color: #af2910; color: #fff; border-color: #af2910;">Item Code</th>
                            <th style="width: 140px; background-color: #af2910; color: #fff; border-color: #af2910;">Unit Measurement</th>
                            <th style="width: 140px; background-color: #af2910; color: #fff; border-color: #af2910;">Standard Cost</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff; border-color: #af2910;">Status</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff; border-color: #af2910;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($itemsubcategories as $itemsubcategory)
                            <tr>
                                <td>{{ $itemsubcategory->id }}</td>
                                <td><div class="fw-semibold">{{ $itemsubcategory->item_name }}</div></td>
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
                                    <span class="badge bg-{{ $itemsubcategory->status_badge_class }}">
                                        {{ $itemsubcategory->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button" class="btn btn-sm btn-warning btn-edit-itemsubcategory"
                                                data-id="{{ $itemsubcategory->id }}"
                                                data-item-name="{{ e($itemsubcategory->item_name) }}"
                                                data-item-code="{{ e($itemsubcategory->item_code ?? '') }}"
                                                data-unit-measurement="{{ e($itemsubcategory->unit_measurement ?? '') }}"
                                                data-standard-cost="{{ $itemsubcategory->standard_cost ?? '' }}"
                                                data-description="{{ e($itemsubcategory->description ?? '') }}"
                                                data-status="{{ e($itemsubcategory->status ?? 'active') }}"
                                                title="Edit">Edit</button>
                                        <form method="POST" action="{{ route('admin.mess.itemsubcategories.destroy', $itemsubcategory->id) }}" class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this item?');">
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

{{-- Create Item Modal --}}
<div class="modal fade" id="createItemSubcategoryModal" tabindex="-1" aria-labelledby="createItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.mess.itemsubcategories.store') }}">
                @csrf
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="createItemSubcategoryModalLabel">Add Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
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
                            <label class="form-label">Unit Measurement <span class="text-danger">*</span></label>
                            <input type="text" name="unit_measurement" class="form-control" value="{{ old('unit_measurement') }}" placeholder="e.g., kg, liter, piece" required>
                            @error('unit_measurement')<div class="text-danger small">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Standard Cost <span class="text-danger">*</span></label>
                            <input type="number" name="standard_cost" class="form-control" step="0.01" min="0" value="{{ old('standard_cost') }}" placeholder="0.00" required>
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

{{-- Edit Item Modal --}}
<div class="modal fade" id="editItemSubcategoryModal" tabindex="-1" aria-labelledby="editItemSubcategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <form id="editItemSubcategoryForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="modal-header border-bottom bg-light">
                    <h5 class="modal-title fw-semibold" id="editItemSubcategoryModalLabel">Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
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
                            <label class="form-label">Unit Measurement <span class="text-danger">*</span></label>
                            <input type="text" name="unit_measurement" id="edit_unit_measurement" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Standard Cost <span class="text-danger">*</span></label>
                            <input type="number" name="standard_cost" id="edit_standard_cost" class="form-control" step="0.01" min="0" placeholder="0.00" required>
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

@include('components.mess-master-datatables', ['tableId' => 'itemSubcategoriesTable', 'searchPlaceholder' => 'Search items...', 'orderColumn' => 1, 'actionColumnIndex' => 6, 'infoLabel' => 'items'])
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('mousedown', function(e) {
        var btn = e.target.closest('.btn-edit-itemsubcategory');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();
        document.getElementById('editItemSubcategoryForm').action = '{{ url("admin/mess/itemsubcategories") }}/' + btn.getAttribute('data-id');
        document.getElementById('edit_item_name').value = btn.getAttribute('data-item-name') || '';
        document.getElementById('edit_item_code_display').value = btn.getAttribute('data-item-code') || '-';
        document.getElementById('edit_unit_measurement').value = btn.getAttribute('data-unit-measurement') || '';
        document.getElementById('edit_standard_cost').value = btn.getAttribute('data-standard-cost') || '';
        document.getElementById('edit_description').value = btn.getAttribute('data-description') || '';
        document.getElementById('edit_status').value = btn.getAttribute('data-status') || 'active';
        new bootstrap.Modal(document.getElementById('editItemSubcategoryModal')).show();
    }, true);
});
</script>
@endpush

<style>
.table thead th { background-color: #af2910 !important; color: #fff !important; }
</style>
@endsection
