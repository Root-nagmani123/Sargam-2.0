@extends('admin.layouts.master')

@section('title', 'Complaint Sub-Category - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Complaint Sub-Category" />
    
    <div id="status-msg" class="mb-0"></div>
    
    <div class="datatables">
        <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
            <div class="card-body p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                    <div>
                        <h4 class="mb-0 fw-bold d-flex align-items-center gap-2">
                            <iconify-icon icon="solar:folder-with-files-bold-duotone" style="font-size: 1.5rem; color: #004a93;"></iconify-icon>
                            Complaint Sub-Category Management
                        </h4>
                        <p class="text-body-secondary small mb-0 mt-1">Manage sub-categories under each complaint category</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-modern shadow-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                        <iconify-icon icon="solar:add-circle-bold" style="font-size: 1.25rem;"></iconify-icon>
                        <span>Add Sub-Category</span>
                    </button>
                </div>
                <hr class="my-4">

                <div class="table-responsive">
                    <table class="table text-nowrap align-middle mb-0" id="subCategoriesTable">
                        <thead>
                            <tr>
                                <th class="text-nowrap">#</th>
                                <th class="text-nowrap">Category</th>
                                <th class="text-nowrap">Sub-Category Name</th>
                                <th class="text-nowrap">Status</th>
                                <th class="text-nowrap">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subCategories as $index => $subCategory)
                            <tr data-category-id="{{ $subCategory->issue_category_master_pk ?? '' }}" data-subcategory-name="{{ $subCategory->issue_sub_category }}" class="align-middle">
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1">{{ $subCategory->category->issue_category ?? '-' }}</span>
                                </td>
                                <td>
                                    <span class="fw-medium">{{ $subCategory->issue_sub_category }}</span>
                                </td>
                                <td>
                                    <div class="form-check form-switch d-inline-flex justify-content-center mb-0">
                                        <input class="form-check-input status-toggle-subcategory" 
                                               type="checkbox" 
                                               role="switch"
                                               data-id="{{ $subCategory->pk }}"
                                               data-url="{{ route('admin.issue-sub-categories.update', $subCategory->pk) }}"
                                               {{ $subCategory->status == 1 ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none p-1 rounded-1 btn-edit-subcategory"
                                                data-id="{{ $subCategory->pk }}"
                                                data-category-id="{{ $subCategory->issue_category_master_pk ?? '' }}"
                                                data-name="{{ e($subCategory->issue_sub_category) }}"
                                                data-status="{{ $subCategory->status }}"
                                                title="Edit Sub-Category">
                                            <iconify-icon icon="solar:pen-bold" style="font-size: 1.2rem;"></iconify-icon>
                                        </button>
                                        <form action="{{ route('admin.issue-sub-categories.destroy', $subCategory->pk) }}" 
                                              method="POST" 
                                              class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this sub-category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger text-decoration-none p-1 rounded-1" title="Delete Sub-Category">
                                                <iconify-icon icon="solar:trash-bin-trash-bold" style="font-size: 1.2rem;"></iconify-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="empty-state py-4">
                                        <div class="empty-state-icon mb-3">
                                            <iconify-icon icon="solar:folder-off-bold-duotone" style="font-size: 4rem; color: var(--bs-secondary);"></iconify-icon>
                                        </div>
                                        <h5 class="fw-semibold mb-2">No Sub-Categories Found</h5>
                                        <p class="text-body-secondary mb-3">Get started by creating your first complaint sub-category.</p>
                                        <button type="button" class="btn btn-primary btn-modern" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                                            <iconify-icon icon="solar:add-circle-bold"></iconify-icon>
                                            Add Your First Sub-Category
                                        </button>
                                    </div>
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

<!-- Add Sub-Category Modal -->
<div class="modal fade" id="addSubCategoryModal" tabindex="-1" aria-labelledby="addSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <form action="{{ route('admin.issue-sub-categories.store') }}" method="POST" id="addSubCategoryForm">
                @csrf
                <div class="modal-header border-0 py-4 text-white" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="addSubCategoryModalLabel">
                        <iconify-icon icon="solar:add-circle-bold" style="font-size: 1.5rem;"></iconify-icon>
                        Add New Sub-Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="issue_category_fk" class="form-label fw-semibold text-body-secondary">
                            <iconify-icon icon="solar:folder-bold" class="me-1"></iconify-icon>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg focus-ring @error('issue_category_master_pk') is-invalid @enderror" 
                                id="issue_category_fk" name="issue_category_master_pk" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                        @error('issue_category_master_pk')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-0">
                        <label for="issue_sub_category" class="form-label fw-semibold text-body-secondary">
                            <iconify-icon icon="solar:tag-bold" class="me-1"></iconify-icon>
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg focus-ring @error('issue_sub_category') is-invalid @enderror" 
                               id="issue_sub_category" 
                               name="issue_sub_category" 
                               placeholder="Enter sub-category name"
                               required>
                        @error('issue_sub_category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-secondary bg-opacity-10 px-4 py-3 gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                        <iconify-icon icon="solar:close-circle-bold" class="me-1"></iconify-icon>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        <iconify-icon icon="solar:check-circle-bold" class="me-1"></iconify-icon>
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sub-Category Modal -->
<div class="modal fade" id="editSubCategoryModal" tabindex="-1" aria-labelledby="editSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3 overflow-hidden">
            <form id="editSubCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header border-0 py-4 text-white" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);">
                    <h5 class="modal-title fw-bold d-flex align-items-center gap-2" id="editSubCategoryModalLabel">
                        <iconify-icon icon="solar:pen-bold" style="font-size: 1.5rem;"></iconify-icon>
                        Edit Sub-Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white opacity-100" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="edit_issue_category_fk" class="form-label fw-semibold text-body-secondary">
                            <iconify-icon icon="solar:folder-bold" class="me-1"></iconify-icon>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg focus-ring" id="edit_issue_category_fk" name="issue_category_master_pk" required>
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit_issue_sub_category" class="form-label fw-semibold text-body-secondary">
                            <iconify-icon icon="solar:tag-bold" class="me-1"></iconify-icon>
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg focus-ring" 
                               id="edit_issue_sub_category" 
                               name="issue_sub_category" 
                               placeholder="Enter sub-category name"
                               required>
                    </div>
                    <div class="mb-0">
                        <label for="edit_status" class="form-label fw-semibold text-body-secondary">
                            <iconify-icon icon="solar:widget-bold" class="me-1"></iconify-icon>
                            Status
                        </label>
                        <select class="form-select form-select-lg focus-ring" id="edit_status" name="status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-body-secondary bg-opacity-10 px-4 py-3 gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">
                        <iconify-icon icon="solar:close-circle-bold" class="me-1"></iconify-icon>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        <iconify-icon icon="solar:refresh-bold" class="me-1"></iconify-icon>
                        Update
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
        var $table = $('#subCategoriesTable');
        if (!$table.length) return;
        var hasDataRows = $table.find('tbody tr').filter(function() { return $(this).find('td[colspan]').length === 0; }).length > 0;
        if (!hasDataRows) return;
        if ($.fn.DataTable.isDataTable($table)) return;
        $table.DataTable({
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
            columnDefs: [
                { orderable: false, targets: [0, 3, 4] }
            ],
            language: {
                search: 'Search sub-categories:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ sub-categories',
                infoEmpty: 'No sub-categories',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching sub-categories found',
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

function editSubCategory(id, categoryId, name, status) {
    document.getElementById('edit_issue_category_fk').value = categoryId != null && categoryId !== '' ? String(categoryId) : '';
    document.getElementById('edit_issue_sub_category').value = name;
    document.getElementById('edit_status').value = status;

    const form = document.getElementById('editSubCategoryForm');
    form.action = "{{ url('admin/issue-sub-categories') }}/" + id;

    const modal = new bootstrap.Modal(document.getElementById('editSubCategoryModal'));
    modal.show();
}

$(document).on('click', '.btn-edit-subcategory', function() {
    const btn = $(this);
    editSubCategory(
        btn.data('id'),
        btn.data('category-id') || null,
        btn.data('name') || '',
        btn.data('status')
    );
});

// Status Toggle Functionality
$(document).ready(function() {
    // Auto-filter functionality - automatically filter when category is selected
    $('#category_filter').on('change', function() {
        const selectedValue = $(this).val();
        if (selectedValue !== null && selectedValue !== undefined) {
            // Show loading indicator
            const form = $('#categoryFilterForm');
            form.find('select').prop('disabled', true);
            
            // Submit the form automatically
            form.submit();
        }
    });
    
    // Status toggle functionality (delegated so it works after DataTable redraws)
    $(document).on('change', '.status-toggle-subcategory', function() {
        const checkbox = $(this);
        const id = checkbox.data('id');
        const url = checkbox.data('url');
        const isChecked = checkbox.is(':checked');
        const status = isChecked ? 1 : 0;
        const actionText = isChecked ? 'activate' : 'deactivate';
        const originalState = !isChecked;
        
        // Disable checkbox during request
        checkbox.prop('disabled', true);
        
        // Get current values from the row
        const row = checkbox.closest('tr');
        const categoryId = row.data('category-id') || '';
        const subCategoryName = row.data('subcategory-name') || row.find('td:eq(2)').text().trim();
        
        // Show confirmation dialog
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to ${actionText} this sub-category?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: `Yes, ${actionText}`,
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            confirmButtonColor: '#198754',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                // Prepare data for PUT request
                const formData = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    _method: 'PUT',
                    status: status,
                    issue_category_master_pk: categoryId || $('#edit_issue_category_fk').val() || '',
                    issue_sub_category: subCategoryName || $('#edit_issue_sub_category').val() || ''
                };
                
                // Submit via AJAX
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: formData,
                    success: function(response) {
                        checkbox.prop('disabled', false);
                        
                        // Update badge
                        const badge = checkbox.closest('td').find('.badge');
                        if (isChecked) {
                            badge.removeClass('bg-secondary').addClass('bg-success').text('ACTIVE');
                        } else {
                            badge.removeClass('bg-success').addClass('bg-secondary').text('INACTIVE');
                        }
                        
                        // Show success message
                        $('#status-msg').html(`
                            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm rounded-3" role="alert">
                                <iconify-icon icon="solar:check-circle-bold" style="font-size: 1.25rem;"></iconify-icon>
                                <span class="flex-grow-1">${response.message || 'Status updated successfully'}</span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        `);
                        
                        // Auto-hide message after 3 seconds
                        setTimeout(function() {
                            $('#status-msg').fadeOut();
                        }, 3000);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Status updated successfully',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        checkbox.prop('disabled', false);
                        // Revert checkbox
                        checkbox.prop('checked', originalState);
                        
                        // Update badge back
                        const badge = checkbox.closest('td').find('.badge');
                        if (originalState) {
                            badge.removeClass('bg-secondary').addClass('bg-success').text('ACTIVE');
                        } else {
                            badge.removeClass('bg-success').addClass('bg-secondary').text('INACTIVE');
                        }
                        
                        const errorMessage = xhr.responseJSON?.message || 'Error updating status';
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            confirmButtonColor: '#dc3545'
                        });
                    }
                });
            } else {
                // User cancelled - revert checkbox
                checkbox.prop('checked', originalState);
                checkbox.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection
