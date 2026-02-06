@extends('admin.layouts.master')

@section('title', 'Complaint Sub-Category - Sargam | Lal Bahadur')

@section('setup_content')
<div class="container-fluid">
    <x-breadcrum title="Complaint Sub-Category" />
    
    <!-- Success/Error Messages -->
    <div id="status-msg"></div>
    
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            Complaint Sub-Category Management
                        </h5>
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">add</i>
                            Add Sub-Category
                        </button>
                    </div>
                </div>
                <div class="card-body p-4">

                    <!-- Table Section -->
                    <div class="table-responsive">
                        <table class="table align-middle mb-0 text-nowrap">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Category</th>
                                    <th>Sub-Category Name</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subCategories as $index => $subCategory)
                                <tr data-category-id="{{ $subCategory->issue_category_master_pk ?? '' }}" data-subcategory-name="{{ $subCategory->issue_sub_category }}">
                                    <td class="text-center fw-semibold text-muted">{{ $loop->iteration }}</td>
                                    <td>
                                        {{ $subCategory->category->issue_category ?? '-' }}
                                    </td>
                                    <td>
<<<<<<< HEAD
                                        <span class="fw-medium">{{ $subCategory->issue_sub_category }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input status-toggle-subcategory" 
                                                       type="checkbox" 
                                                       role="switch"
                                                       data-id="{{ $subCategory->pk }}"
                                                       data-url="{{ route('admin.issue-sub-categories.update', $subCategory->pk) }}"
                                                       {{ $subCategory->status == 1 ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex justify-content-center gap-2">
                                            <a href="javascript:void(0)" class="text-primary" 
                                                    onclick="editSubCategory({{ $subCategory->pk }}, {{ $subCategory->issue_category_master_pk ?? 'null' }}, {{ json_encode($subCategory->issue_sub_category) }}, {{ $subCategory->status }})"
                                                    title="Edit Sub-Category">
                                                <i class="material-icons material-symbols-rounded" style="font-size: 18px;">edit</i>
                                            </a>
                                            <form action="{{ route('admin.issue-sub-categories.destroy', $subCategory->pk) }}" 
                                                  method="POST" 
                                                  class="d-inline" 
                                                  onsubmit="return confirm('Are you sure you want to delete this sub-category?');">
                                                @csrf
                                                @method('DELETE')
                                                <a href="javascript:void(0)" class="text-primary"
                                                        title="Delete Sub-Category">
                                                    <i class="material-icons material-symbols-rounded" style="font-size: 18px;">delete</i>
                                                </a>
                                            </form>
                                        </div>
=======
                                        <button type="button" class="btn btn-sm btn-warning" 
                                                onclick="editSubCategory({{ $subCategory->pk }}, {{ $subCategory->issue_category_master_pk }}, '{{ addslashes($subCategory->issue_sub_category) }}', '{{ addslashes($subCategory->description) }}', {{ $subCategory->status }})">
                                            <iconify-icon icon="solar:pen-bold"></iconify-icon> Edit
                                        </button>
                                        <form action="{{ route('admin.issue-sub-categories.destroy', $subCategory->pk) }}" 
                                              method="POST" class="d-inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this sub-category?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <iconify-icon icon="solar:trash-bin-trash-bold"></iconify-icon> Delete
                                            </button>
                                        </form>
>>>>>>> 3fd64ef0 (ui bugs fixes and approval for id card)
                                    </td>
                                </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <iconify-icon icon="solar:folder-off-bold-duotone"></iconify-icon>
                                                </div>
                                                <h6 class="text-muted mb-2">No Sub-Categories Found</h6>
                                                <p class="text-muted small mb-0">Start by adding your first complaint sub-category.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($subCategories->hasPages())
                    <div class="mt-4 d-flex justify-content-center">
                        {{ $subCategories->links() }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Sub-Category Modal -->
<div class="modal fade" id="addSubCategoryModal" tabindex="-1" aria-labelledby="addSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form action="{{ route('admin.issue-sub-categories.store') }}" method="POST" id="addSubCategoryForm">
                @csrf
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);">
                    <h5 class="modal-title fw-semibold" id="addSubCategoryModalLabel">
                        <i class="material-icons material-symbols-rounded me-2">add_circle</i>
                        Add New Sub-Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
<<<<<<< HEAD
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="issue_category_fk" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">category</i>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg @error('issue_category_master_pk') is-invalid @enderror" 
                                id="issue_category_fk" name="issue_category_master_pk" required>
=======
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="issue_category_master_pk" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select @error('issue_category_master_pk') is-invalid @enderror" 
                                id="issue_category_master_pk" name="issue_category_master_pk" required>
>>>>>>> 3fd64ef0 (ui bugs fixes and approval for id card)
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                        @error('issue_category_master_pk')
<<<<<<< HEAD
                            <div class="invalid-feedback d-block">{{ $message }}</div>
=======
                            <div class="invalid-feedback">{{ $message }}</div>
>>>>>>> 3fd64ef0 (ui bugs fixes and approval for id card)
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label for="issue_sub_category" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">label</i>
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg @error('issue_sub_category') is-invalid @enderror" 
                               id="issue_sub_category" 
                               name="issue_sub_category" 
                               placeholder="Enter sub-category name"
                               required>
                        @error('issue_sub_category')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer border-top bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">close</i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">check</i>
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sub-Category Modal -->
<div class="modal fade" id="editSubCategoryModal" tabindex="-1" aria-labelledby="editSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form id="editSubCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #004a93 0%, #0066cc 100%);">
                    <h5 class="modal-title fw-semibold" id="editSubCategoryModalLabel">
                        <i class="material-icons material-symbols-rounded me-2">edit</i>
                        Edit Sub-Category
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
<<<<<<< HEAD
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="edit_issue_category_fk" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">category</i>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg" id="edit_issue_category_fk" name="issue_category_master_pk" required>
=======
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_issue_category_master_pk" class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_issue_category_master_pk" name="issue_category_master_pk" required>
>>>>>>> 3fd64ef0 (ui bugs fixes and approval for id card)
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="edit_issue_sub_category" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">label</i>
                            Sub-Category Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control form-control-lg" 
                               id="edit_issue_sub_category" 
                               name="issue_sub_category" 
                               placeholder="Enter sub-category name"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">toggle_on</i>
                            Status
                        </label>
                        <select class="form-select form-select-lg" id="edit_status" name="status" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light p-3">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">close</i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">update</i>
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
<<<<<<< HEAD
function editSubCategory(id, categoryId, name, status) {
    document.getElementById('edit_issue_category_fk').value = categoryId != null ? String(categoryId) : '';
=======
function editSubCategory(id, categoryId, name, description, status) {
    document.getElementById('edit_issue_category_master_pk').value = categoryId;
>>>>>>> 3fd64ef0 (ui bugs fixes and approval for id card)
    document.getElementById('edit_issue_sub_category').value = name;
    document.getElementById('edit_status').value = status;
    
    const form = document.getElementById('editSubCategoryForm');
    form.action = "{{ url('admin/issue-sub-categories') }}/" + id;
    
    const modal = new bootstrap.Modal(document.getElementById('editSubCategoryModal'));
    modal.show();
}

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
    
    // Status toggle functionality
    $('.status-toggle-subcategory').on('change', function() {
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
                            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                                <i class="material-icons material-symbols-rounded me-2">check_circle</i>
                                ${response.message || 'Status updated successfully'}
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
    
    // Show success message if redirected with success
    @if(session('success'))
        $('#status-msg').html(`
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="material-icons material-symbols-rounded me-2">check_circle</i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        setTimeout(function() {
            $('#status-msg').fadeOut();
        }, 3000);
    @endif
    
    @if(session('error'))
        $('#status-msg').html(`
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="material-icons material-symbols-rounded me-2">error</i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        setTimeout(function() {
            $('#status-msg').fadeOut();
        }, 3000);
    @endif
});
</script>
@endsection
