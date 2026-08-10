@extends('admin.layouts.master')

@section('title', 'Manage Sub-Categories')

@push('styles')
{{-- Shared Centcom index chrome — same file the Manage Categories grid uses, so
     the two pages cannot drift apart. See docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    // Only the category scope goes to the server now — search, sort and paging
    // are DataTables'. ?cols= is appended by iscUpdateExportCols().
    $exportQuery = ['category_id' => $categoryId];
@endphp
<div class="container-fluid ic-page">
    <x-breadcrum title="Manage Sub-Categories" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="iscAddBtn" data-bs-toggle="modal" data-bs-target="#addSubCategoryModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Sub-Category</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 ic-secondary-actions">
        {{-- More than one download format → dropdown, per §1 of the doc. --}}
        <div class="dropdown">
            <button type="button" id="iscDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="iscDownloadToggle">
                <li><a class="dropdown-item" id="iscDownloadLink"
                       href="{{ route('admin.issue-sub-categories.export', array_merge(['format' => 'csv'], $exportQuery)) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV</a></li>
                <li><a class="dropdown-item" id="iscExcelLink"
                       href="{{ route('admin.issue-sub-categories.export', array_merge(['format' => 'excel'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)</a></li>
                <li><a class="dropdown-item" id="iscPdfLink"
                       href="{{ route('admin.issue-sub-categories.export', array_merge(['format' => 'pdf'], $exportQuery)) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF</a></li>
            </ul>
        </div>
        <a href="{{ route('admin.issue-sub-categories.export', array_merge(['format' => 'print'], $exportQuery)) }}"
           id="iscPrintLink"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: category filter left, columns + search right (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 ic-toolbar">
                <form method="GET" action="{{ route('admin.issue-sub-categories.index') }}"
                      class="d-flex flex-wrap align-items-center gap-3" id="iscFilterForm">



                    <span class="programme-dt-filters-label">Filters</span>
                    <div class="programme-dt-filter-select">
                        <select name="category_id" class="form-select" id="iscCategoryFilter" aria-label="Filter by category">
                            <option value="">Category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->pk }}" {{ (string) $categoryId === (string) $category->pk ? 'selected' : '' }}>
                                    {{ $category->issue_category }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($categoryId !== null)
                        <a href="{{ route('admin.issue-sub-categories.index') }}" class="btn programme-dt-btn-reset">Reset Filters</a>
                    @endif
                </form>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="iscBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#iscColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Always-visible search box: datatable-global-ui.js moves DataTables'
                         own filter in here, so it filters as you type instead of
                         reloading the page on Enter. --}}
                    <div id="iscDtSearch" class="programme-dt-search" data-dt-search-for="issueSubCategoriesTable"></div>
                </div>
            </div>

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
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form action="{{ route('admin.issue-sub-categories.store') }}" method="POST" id="addSubCategoryForm">
                @csrf
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="addSubCategoryModalLabel">Add Sub-Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="issue_category_fk" class="ic-form-label">Category<span class="ic-req">*</span></label>
                            <select class="form-select ic-control" id="issue_category_fk" name="issue_category_master_pk" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-0">
                            <label for="issue_sub_category" class="ic-form-label">Sub-Category Name<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="issue_sub_category"
                                   name="issue_sub_category" placeholder="e.g. Provide web service"
                                   maxlength="255" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Add Sub-Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Sub-Category Modal -->
<div class="modal fade" id="editSubCategoryModal" tabindex="-1" aria-labelledby="editSubCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form id="editSubCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="editSubCategoryModalLabel">Edit Sub-Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Same card / label / control language as Add, plus Status. --}}
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="edit_issue_category_fk" class="ic-form-label">Category<span class="ic-req">*</span></label>
                            <select class="form-select ic-control" id="edit_issue_category_fk" name="issue_category_master_pk" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->pk }}">{{ $category->issue_category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_issue_sub_category" class="ic-form-label">Sub-Category Name<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="edit_issue_sub_category"
                                   name="issue_sub_category" placeholder="e.g. Provide web service"
                                   maxlength="255" required>
                        </div>
                        <div class="mb-0">
                            <label for="edit_status" class="ic-form-label">Status<span class="ic-req">*</span></label>
                            <select class="form-select ic-control" id="edit_status" name="status" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Update Sub-Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="iscColumnVisibilityModal" tabindex="-1" aria-labelledby="iscColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="iscColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="issueSubCatColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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
            text: 'Delete "' + name + '"? This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d92d20',
            reverseButtons: true
        }).then(function (result) {
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

    /* ── Edit modal ──────────────────────────────────────────────────────── */
    $(document).on('click', '.isc-edit-btn', function () {
        var $btn = $(this);
        $('#edit_issue_category_fk').val($btn.data('category') ? String($btn.data('category')) : '');
        $('#edit_issue_sub_category').val($btn.data('name'));
        $('#edit_status').val(String($btn.data('status')) === '1' ? '1' : '0');
        $('#editSubCategoryForm').attr('action', "{{ url('admin/issue-sub-categories') }}/" + $btn.data('id'));

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editSubCategoryModal')).show();
    });

    /* ── Add modal: reset on close so a stale entry can't leak back in ───── */
    document.getElementById('addSubCategoryModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('addSubCategoryForm').reset();
    });
});
</script>
@endpush
