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
                        {{-- id is what the DataTable in @@section('scripts') binds to. --}}
                        <table class="table align-middle mb-0 text-nowrap" id="issueSubCategoriesTable">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th>Category</th>
                                    <th>Sub-Category Name</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            {{-- Rows come from IssueSubCategoryController::data() over
                                 ajax (server-side paging), so this stays empty. --}}
                            <tbody></tbody>
                        </table>
                    </div>

                    {{-- No Blade pager: the DataTable pages this grid from the server,
                         one draw at a time. --}}
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
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="issue_category_fk" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">category</i>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg @error('issue_category_master_pk') is-invalid @enderror" 
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
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label for="edit_issue_category_fk" class="form-label fw-semibold">
                            <i class="material-icons material-symbols-rounded me-1" style="font-size: 18px;">category</i>
                            Category <span class="text-danger">*</span>
                        </label>
                        <select class="form-select form-select-lg" id="edit_issue_category_fk" name="issue_category_master_pk" required>
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
/* Server-side DataTable — search, sort and paging all run in SQL via data(),
   so the browser only ever holds the page it is showing. The category filter
   rides along on the same ajax call. */
(function () {
    'use strict';
    document.addEventListener('DOMContentLoaded', function () {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable) { return; }

        var $table = $('#issueSubCategoriesTable');
        if (!$table.length || $.fn.DataTable.isDataTable($table)) { return; }

        var dt = $table.DataTable({
            serverSide: true,
            /* datatable-global-ui.js turns DataTables' native ordering OFF for
               server-side tables unless this opt-in is present, and sorts only the
               rows already loaded instead. We want ORDER BY over the whole set. */
            sargamServerOrder: true,
            processing: true,
            ajax: {
                url: '{{ route('admin.issue-sub-categories.data') }}',
                data: function (d) {
                    /* Prefer a live dropdown if one is present, otherwise fall back to
                       the value this page was loaded with — otherwise a ?category_id=
                       deep link would silently stop filtering once rows moved to ajax. */
                    var $filter = $('#category_filter');
                    d.category_id = $filter.length
                        ? ($filter.val() || '')
                        : @json(request('category_id', ''));
                }
            },
            order: [[1, 'asc']],                 // Category A→Z
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            searchDelay: 400,
            columns: [
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center fw-semibold text-muted' },
                { data: 'category', name: 'category' },
                { data: 'sub_category', name: 'sub_category' },
                { data: 'status', name: 'status', searchable: false, className: 'text-center' },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                processing: 'Loading…',
                search: 'Search sub-categories:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ sub-categories',
                infoEmpty: 'No sub-categories',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching sub-categories found',
                emptyTable: 'No Sub-Categories Found — start by adding your first complaint sub-category.',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            },
            drawCallback: function () {
                if (typeof window.adjustAllDataTables === 'function') {
                    try { window.adjustAllDataTables(); } catch (e) { /* noop */ }
                }
            }
        });

        // Expose it so the status-toggle handler can redraw after a successful PUT.
        window.issueSubCategoriesDt = dt;
    });
})();

function editSubCategory(id, categoryId, name, status) {
    document.getElementById('edit_issue_category_fk').value = categoryId != null ? String(categoryId) : '';
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
    
    // Status toggle functionality — delegated from document, because the rows are
    // injected by the server-side DataTable on every draw and a direct binding
    // would only ever attach to the first page.
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

                        /* The row was rendered by the server, so redraw the current page
                           rather than patching cells here — keeps the grid authoritative.
                           (The old badge-patching below is a no-op now: this column
                           renders a switch, not a badge.) */
                        if (window.issueSubCategoriesDt) {
                            window.issueSubCategoriesDt.ajax.reload(null, false);
                        }

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
