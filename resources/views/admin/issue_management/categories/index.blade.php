@extends('admin.layouts.master')

@section('title', 'Complaint Category - Sargam | Lal Bahadur')

@section('css')
<style>
.modal-body {
    background-color: #fff !important;
    color: #212529 !important;
}
.modal-content {
    background-color: #fff !important;
}

/* Enhanced Card Design */
.issue-category-card {
    border-radius: 0.75rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 74, 147, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.issue-category-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.issue-category-card .card-header-modern {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border-bottom: 2px solid rgba(0, 74, 147, 0.1);
    padding: 1.5rem;
}

.issue-category-card .card-header-modern::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #004a93 0%, #0066cc 100%);
}

/* Enhanced Table */
.issue-category-table {
    margin-bottom: 0;
}

.issue-category-table thead th {
    background: linear-gradient(180deg, #f8f9fa 0%, #ffffff 100%);
    font-weight: 600;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    padding: 1rem 0.75rem;
    vertical-align: middle;
}

.issue-category-table tbody tr {
    transition: all 0.2s ease;
    border-bottom: 1px solid #f0f0f0;
}

.issue-category-table tbody tr:hover {
    background-color: rgba(0, 74, 147, 0.03);
    transform: scale(1.001);
}

.issue-category-table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    color: #212529;
}

/* Enhanced Badges */
.badge-modern {
    padding: 0.5em 0.75em;
    font-weight: 600;
    font-size: 0.75rem;
    border-radius: 0.5rem;
    letter-spacing: 0.02em;
}

/* Enhanced Buttons */
.btn-action-group {
    display: inline-flex;
    gap: 0.5rem;
    align-items: center;
}

.btn-modern {
    border-radius: 0.5rem;
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-modern:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.btn-modern:active {
    transform: translateY(0);
}

/* Status Toggle Enhancement */
.form-check-input.status-toggle {
    width: 3rem;
    height: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.form-check-input.status-toggle:checked {
    background-color: #198754;
    border-color: #198754;
}

.form-check-input.status-toggle:focus {
    box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
}

/* Empty State */
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #6c757d;
}

.empty-state-icon {
    font-size: 4rem;
    opacity: 0.3;
    margin-bottom: 1rem;
}

/* Enhanced Modal - Matching Design */
.modal-content {
    border-radius: 0.5rem;
    border: 1px solid #e0e0e0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.modal-header {
    border-radius: 0.5rem 0.5rem 0 0;
    padding: 1.5rem 1.5rem 0.5rem 1.5rem;
    border-bottom: none;
}

.modal-header .modal-title {
    font-size: 1.25rem;
    color: #212529;
}

.modal-header .text-muted {
    font-size: 0.875rem;
    font-weight: 400;
}

.modal-body {
    padding: 1rem 1.5rem;
}

.modal-footer {
    border-top: none;
    padding: 0.5rem 1.5rem 1.5rem 1.5rem;
}

/* Form Input Styling */
#addCategoryModal .form-control {
    border-radius: 0.375rem;
    border: 1px solid #ced4da;
    padding: 0.5rem 0.75rem;
    font-size: 0.9375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

#addCategoryModal .form-control:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.2rem rgba(0, 74, 147, 0.15);
    outline: 0;
}

#addCategoryModal .form-label {
    font-size: 0.9375rem;
    color: #212529;
    margin-bottom: 0.5rem;
}

#addCategoryModal .form-text {
    font-size: 0.8125rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

#addCategoryModal .form-text iconify-icon {
    color: #6c757d;
}

/* Required Fields Disclaimer */
#addCategoryModal .text-muted.small {
    font-size: 0.8125rem;
    line-height: 1.5;
}

/* Button Styling */
#addCategoryModal .btn-primary {
    background-color: #004a93;
    border-color: #004a93;
    border-radius: 0.375rem;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    transition: all 0.2s ease;
}

#addCategoryModal .btn-primary:hover {
    background-color: #003d7a;
    border-color: #003d7a;
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 74, 147, 0.3);
}

#addCategoryModal .btn-outline-primary {
    border-color: #004a93;
    color: #004a93;
    border-radius: 0.375rem;
    padding: 0.5rem 1.5rem;
    font-weight: 500;
    background-color: transparent;
    transition: all 0.2s ease;
}

#addCategoryModal .btn-outline-primary:hover {
    background-color: #004a93;
    border-color: #004a93;
    color: #fff;
    transform: translateY(-1px);
}

/* Sub-Category Buttons */
#addCategoryModal .add-field-btn,
#addCategoryModal .remove-field-btn {
    border: 1.5px solid #004a93;
    color: #004a93;
    background-color: transparent;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

#addCategoryModal .add-field-btn:hover {
    background-color: #004a93;
    color: #fff;
    border-color: #004a93;
    transform: scale(1.1);
}

#addCategoryModal .remove-field-btn {
    border-color: #dc3545;
    color: #dc3545;
}

#addCategoryModal .remove-field-btn:hover {
    background-color: #dc3545;
    color: #fff;
    border-color: #dc3545;
    transform: scale(1.1);
}

#addCategoryModal .add-field-btn:active,
#addCategoryModal .remove-field-btn:active {
    transform: scale(0.95);
}

#addCategoryModal .add-field-btn iconify-icon,
#addCategoryModal .remove-field-btn iconify-icon {
    color: inherit;
}

/* Category Field Groups */
#addCategoryModal .category-field-group {
    transition: all 0.3s ease;
}

#addCategoryModal .category-field-group hr {
    border-color: #e0e0e0;
    opacity: 0.5;
}

/* Form Controls Enhancement */
.form-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.form-control:focus,
.form-select:focus {
    border-color: #004a93;
    box-shadow: 0 0 0 0.25rem rgba(0, 74, 147, 0.15);
}

/* Pagination Enhancement */
.pagination {
    margin-top: 1.5rem;
}

.page-link {
    border-radius: 0.5rem;
    margin: 0 0.25rem;
    border: 1px solid #dee2e6;
    color: #004a93;
    transition: all 0.2s ease;
}

.page-link:hover {
    background-color: rgba(0, 74, 147, 0.1);
    border-color: #004a93;
    transform: translateY(-1px);
}

.page-item.active .page-link {
    background-color: #004a93;
    border-color: #004a93;
}
</style>
@endsection

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Complaint Category" />
    <div class="datatables">
        <div class="card issue-category-card">
            <div class="card-header-modern position-relative">
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
            </div>
            <div class="card-body p-4">
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

                <div class="table-responsive rounded-3 border">
                    <table class="table text-nowrap mb-0">
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

                @if($categories->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $categories->links() }}
                </div>
                @endif
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
