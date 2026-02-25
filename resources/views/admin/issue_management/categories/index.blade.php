@extends('admin.layouts.master')

@section('title', 'Complaint Category - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Complaint Category" />
    <div class="datatables">
        <div class="card issue-category-card">
            <div class="card-body p-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div>
                        <h4 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:folder-bold-duotone" style="font-size: 1.5rem; color: #004a93;"></iconify-icon>
                            Complaint Category Management
                        </h4>
                        <p class="text-muted small mb-0 mt-1">Manage and organize complaint categories</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-modern shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                        <i class="material-icons material-symbols-rounded">add</i>
                        <span>Add Category</span>
                    </button>
                </div>
                <hr class="my-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                        <iconify-icon icon="solar:check-circle-bold" style="font-size: 1.25rem;"></iconify-icon>
                        <div class="flex-grow-1">{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                        <iconify-icon icon="solar:danger-triangle-bold" style="font-size: 1.25rem;"></iconify-icon>
                        <div class="flex-grow-1">{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table issue-category-table text-nowrap mb-0" id="categoriesTable">
                        <thead>
                            <tr>
                                <th class="text-nowrap">#</th>
                                <th class="text-nowrap">Category Name</th>
                                <th class="text-nowrap">Description</th>
                                <th class="text-nowrap">Sub-Categories</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $index => $category)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $category->issue_category }}</td>
                                <td>{{ Str::limit($category->description ?? 'No description', 50) }}</td>
                                <td>{{ $category->subCategories->count() }}</td>
                                <td>
                                    <div class="form-check form-switch d-inline-flex justify-content-center">
                                        <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                            data-table="issue_category_master" data-column="status" data-id="{{ $category->pk }}" 
                                            {{ $category->status == 1 ? 'checked' : '' }}>
                                    </div>
                                <td>
                                    <div class="btn-action-group justify-content-center">
                                        <a href="javascript:void(0)" class="text-primary" onclick="editCategory({{ $category->pk }}, '{{ addslashes($category->issue_category) }}', '{{ addslashes($category->description) }}', {{ $category->status }})" title="Edit Category">
                                            <i class="material-icons material-symbols-rounded">edit</i>
                                        </a>
                                        <form action="{{ route('admin.issue-categories.destroy', $category->pk) }}" 
                                              method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this category?');">
                                            @csrf
                                            @method('DELETE')
                                            <a class="text-primary" title="Delete Category">
                                                <i class="material-icons material-symbols-rounded">delete</i>
                                            </a>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="empty-state">
                                    <div class="empty-state-icon">
                                        <iconify-icon icon="solar:folder-off-bold-duotone"></iconify-icon>
                                    </div>
                                    <h5 class="fw-semibold mb-2">No Categories Found</h5>
                                    <p class="text-muted mb-3">Get started by creating your first complaint category.</p>
                                    <button type="button" class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                        <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
                                        Add Your First Category
                                    </button>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('admin.issue-categories.store') }}" method="POST">
                @csrf
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <div class="w-100">
                        <h4 class="modal-title fw-bold mb-2" id="addCategoryModalLabel">Define Complaint Category</h4>
                        <p class="text-muted mb-0 small">Please add the Complaint Sub Category</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <div id="categoryFieldsContainer">
                        <!-- First Field Set -->
                        <div class="category-field-group mb-4" data-index="0">
                            <div class="mb-3">
                                <label class="form-label fw-bold mb-2">
                                    Complaint <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control complaint-field" 
                                       name="categories[0][issue_category]" 
                                       placeholder="Enter Complaint"
                                       required>
                                <div class="form-text mt-2 d-flex align-items-center gap-1 text-muted">
                                    <iconify-icon icon="solar:info-circle-bold" style="font-size: 0.875rem;"></iconify-icon>
                                    <span>Enter Complaint</span>
                                </div>
                            </div>
                            <div class="mb-3 position-relative">
                                <label class="form-label fw-bold mb-2">
                                    Description <span class="text-danger">*</span>
                                </label>
                                <div class="d-flex align-items-start gap-2">
                                    <div class="flex-grow-1">
                                        <input type="text" 
                                               class="form-control description-field" 
                                               name="categories[0][description]" 
                                               placeholder="Enter Description"
                                               required>
                                        <div class="form-text mt-2 d-flex align-items-center gap-1 text-muted">
                                            <iconify-icon icon="solar:info-circle-bold" style="font-size: 0.875rem;"></iconify-icon>
                                            <span>Enter Description</span>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 align-items-end" style="padding-bottom: 0.5rem;">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-0 d-flex align-items-center justify-content-center add-field-btn" style="width: 32px; height: 32px; border-width: 1.5px;" title="Add Another Field">
                                            <iconify-icon icon="solar:add-circle-bold" style="font-size: 1.25rem;"></iconify-icon>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center remove-field-btn" style="width: 32px; height: 32px; border-width: 1.5px; display: none;" title="Remove This Field">
                                            <iconify-icon icon="solar:minus-circle-bold" style="font-size: 1.25rem;"></iconify-icon>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <hr class="my-4" style="display: none;">
                        </div>
                    </div>
                    <div class="mt-4 mb-2">
                        <p class="text-muted small mb-0">
                            <span class="text-danger">*</span><strong>Required Fields:</strong> All marked fields are mandatory for registration
                        </p>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4 gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header bg-primary text-white">
                    <div class="d-flex align-items-center gap-2">
                        <iconify-icon icon="solar:pen-bold" style="font-size: 1.5rem;"></iconify-icon>
                        <h5 class="modal-title mb-0 fw-bold" id="editCategoryModalLabel">Edit Category</h5>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <label for="edit_issue_category" class="form-label d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:folder-bold" style="color: #004a93;"></iconify-icon>
                            Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="edit_issue_category" 
                               name="issue_category" 
                               placeholder="Enter category name"
                               required>
                    </div>
                    <div class="mb-4">
                        <label for="edit_description" class="form-label d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:document-text-bold" style="color: #004a93;"></iconify-icon>
                            Description
                        </label>
                        <textarea class="form-control" 
                                  id="edit_description" 
                                  name="description" 
                                  rows="4"
                                  placeholder="Enter category description (optional)"></textarea>
                        <div class="form-text">Provide a brief description to help users understand this category.</div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:settings-bold" style="color: #004a93;"></iconify-icon>
                            Status
                        </label>
                        <select class="form-select form-select-lg" id="edit_status" name="status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <div class="form-text">Note: You can also toggle status directly from the table using the switch.</div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-modern" data-bs-dismiss="modal">
                        <iconify-icon icon="solar:close-circle-bold"></iconify-icon>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-success btn-modern shadow-sm">
                        <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
                        Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable) return;
        var $table = $('#categoriesTable');
        if (!$table.length) return;
        var hasDataRows = $table.find('tbody tr').filter(function() { return $(this).find('td[colspan]').length === 0; }).length > 0;
        if (!hasDataRows) return;
        if ($.fn.DataTable.isDataTable($table)) return;
        $table.DataTable({
            order: [[1, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            columnDefs: [
                { orderable: false, targets: [0, 4, 5] }
            ],
            language: {
                search: 'Search categories:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ categories',
                infoEmpty: 'No categories',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching categories found',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            },
            drawCallback: function() {
                if (typeof window.adjustAllDataTables === 'function') {
                    try { window.adjustAllDataTables(); } catch (e) {}
                }
            }
        });
    });
})();

function editCategory(id, name, description, status) {
    document.getElementById('edit_issue_category').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_status').value = status;
    
    const form = document.getElementById('editCategoryForm');
    form.action = "{{ url('admin/issue-categories') }}/" + id;
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

// Dynamic Field Management
let fieldIndex = 1;

// Add new field group
$(document).on('click', '.add-field-btn', function() {
    const container = $('#categoryFieldsContainer');
    const firstGroup = container.find('.category-field-group').first();
    const newGroup = firstGroup.clone();
    
    // Update index
    newGroup.attr('data-index', fieldIndex);
    
    // Update field names
    newGroup.find('.complaint-field').attr('name', `categories[${fieldIndex}][issue_category]`).val('');
    newGroup.find('.description-field').attr('name', `categories[${fieldIndex}][description]`).val('');
    
    // Show remove button and separator
    newGroup.find('.remove-field-btn').show();
    newGroup.find('hr').show();
    
    // Hide add button in previous group
    $(this).closest('.category-field-group').find('.add-field-btn').hide();
    
    // Append new group
    container.append(newGroup);
    
    fieldIndex++;
    
    // Scroll to new field
    $('html, body').animate({
        scrollTop: newGroup.offset().top - 100
    }, 300);
});

// Remove field group
$(document).on('click', '.remove-field-btn', function() {
    const group = $(this).closest('.category-field-group');
    const container = $('#categoryFieldsContainer');
    const totalGroups = container.find('.category-field-group').length;
    
    // Don't remove if only one group
    if (totalGroups <= 1) {
        return;
    }
    
    // Show add button in previous group
    const prevGroup = group.prev('.category-field-group');
    if (prevGroup.length) {
        prevGroup.find('.add-field-btn').show();
        prevGroup.find('hr').hide();
    }
    
    // Remove current group
    group.fadeOut(300, function() {
        $(this).remove();
        
        // Re-index remaining fields
        reindexFields();
    });
});

// Re-index fields after removal
function reindexFields() {
    $('#categoryFieldsContainer .category-field-group').each(function(index) {
        $(this).attr('data-index', index);
        $(this).find('.complaint-field').attr('name', `categories[${index}][issue_category]`);
        $(this).find('.description-field').attr('name', `categories[${index}][description]`);
    });
    
    fieldIndex = $('#categoryFieldsContainer .category-field-group').length;
}

// Reset form when modal is closed
$('#addCategoryModal').on('hidden.bs.modal', function() {
    const container = $('#categoryFieldsContainer');
    
    // Keep only first group
    container.find('.category-field-group:not(:first)').remove();
    
    // Reset first group
    const firstGroup = container.find('.category-field-group').first();
    firstGroup.find('input').val('');
    firstGroup.find('.remove-field-btn').hide();
    firstGroup.find('.add-field-btn').show();
    firstGroup.find('hr').hide();
    
    // Reset index
    fieldIndex = 1;
    
    // Clear validation errors
    container.find('.is-invalid').removeClass('is-invalid');
    container.find('.invalid-feedback').remove();
});

// Form submission handler
$('#addCategoryModal form').on('submit', function(e) {
    const form = $(this);
    let isValid = true;
    let hasData = false;
    
    // Clear previous validation
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();
    
    // Validate all fields
    $('#categoryFieldsContainer .category-field-group').each(function() {
        const complaint = $(this).find('.complaint-field').val().trim();
        const description = $(this).find('.description-field').val().trim();
        
        if (complaint || description) {
            hasData = true;
            
            if (!complaint) {
                isValid = false;
                const field = $(this).find('.complaint-field');
                field.addClass('is-invalid');
                if (!field.next('.invalid-feedback').length) {
                    field.after('<div class="invalid-feedback">Complaint field is required.</div>');
                }
            }
            
            if (!description) {
                isValid = false;
                const field = $(this).find('.description-field');
                field.addClass('is-invalid');
                if (!field.next('.invalid-feedback').length) {
                    field.after('<div class="invalid-feedback">Description field is required.</div>');
                }
            }
        }
    });
    
    if (!hasData) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'No Data',
            text: 'Please add at least one category entry.',
            confirmButtonColor: '#004a93'
        });
        return false;
    }
    
    if (!isValid) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Validation Error',
            text: 'Please fill all required fields for each category entry.',
            confirmButtonColor: '#004a93'
        });
        return false;
    }
    
    // Disable empty field groups before submission to prevent sending empty data
    $('#categoryFieldsContainer .category-field-group').each(function() {
        const complaint = $(this).find('.complaint-field').val().trim();
        const description = $(this).find('.description-field').val().trim();
        
        if (!complaint || !description) {
            $(this).find('input').prop('disabled', true);
        }
    });
    
    // Form will submit normally with valid data
});
</script>
@endsection
