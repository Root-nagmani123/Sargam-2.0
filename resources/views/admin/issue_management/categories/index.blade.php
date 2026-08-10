@extends('admin.layouts.master')

@section('title', 'Manage Categories')

@push('styles')
{{-- Shared Centcom index chrome — same file the Manage Sub-Categories grid uses,
     so the two pages cannot drift apart. See docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/issue-management-admin.css') }}?v={{ @filemtime(public_path('css/issue-management-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid ic-page">
    <x-breadcrum title="Manage Categories" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="icAddBtn" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Category</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Secondary actions (Download / Print) — above the card, per §1 --}}
    <div class="d-flex flex-wrap justify-content-end gap-2 mb-3 ic-secondary-actions">
        {{-- Searching/sorting is client-side now, so the server has no filter to
             mirror: these export the full list. --}}
        {{-- ?cols= is stamped on by icUpdateExportCols() so both exports carry the
             same columns the grid is showing. Searching/sorting is client-side, so
             the row set is always the full list. --}}
        {{-- More than one download format → dropdown, per §1 of the doc. --}}
        <div class="dropdown">
            <button type="button" id="icDownloadToggle"
                    class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" title="Download">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="icDownloadToggle">
                <li>
                    <a class="dropdown-item" id="icDownloadLink"
                       href="{{ route('admin.issue-categories.export', ['format' => 'csv']) }}">
                        <i class="bi bi-filetype-csv me-1" aria-hidden="true"></i> CSV
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="icExcelLink"
                       href="{{ route('admin.issue-categories.export', ['format' => 'excel']) }}">
                        <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i> Excel (.xlsx)
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" id="icPdfLink"
                       href="{{ route('admin.issue-categories.export', ['format' => 'pdf']) }}">
                        <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i> PDF
                    </a>
                </li>
            </ul>
        </div>
        <a href="{{ route('admin.issue-categories.export', ['format' => 'print']) }}"
           id="icPrintLink"
           target="_blank" rel="noopener" class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
            <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
        </a>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: columns + search on the right (§2) --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4 ic-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="icBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#icColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="icDtSearch" class="programme-dt-search" data-dt-search-for="issueCategoriesTable"></div>
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
                    {{-- id is what the DataTable in @@section('scripts') binds to; without
                         it that init silently no-ops and the grid loses search/sort/paging. --}}
                    <table class="table text-nowrap mb-0" id="categoriesTable">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Category</th>
                                <th scope="col">Description</th>
                                <th scope="col">Sub-Categories</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        {{-- Rows come from IssueCategoryController::data() over ajax
                             (server-side paging), so this stays empty. --}}
                        <tbody></tbody>
                    </table>
                </div>

                {{-- No server pager: the controller sends every row and the
                     DataTable pages them in the browser, so its search and sort
                     cover the whole set instead of one server page. --}}
            </div>

        </div>
    </div>
</div>

<!-- Add Category Modal (supports adding several categories in one go) -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            {{-- novalidate: the submit handler below drops fully-blank extra cards.
                 Native `required` would block submit first and the user could never
                 get past an extra card they added and left empty. --}}
            <form action="{{ route('admin.issue-categories.store') }}" method="POST" novalidate>
                @csrf
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body ic-modal-body">
                    <div id="categoryFieldsContainer">
                        {{-- Template group. Clones of THIS node become groups 1..n, so keep it
                             free of any state the clone shouldn't inherit — syncFieldControls()
                             decides +/− visibility for every group after each change. --}}
                        <div class="ic-field-card category-field-group" data-index="0">
                            <div class="mb-3">
                                <label class="ic-form-label">Complaint<span class="ic-req">*</span></label>
                                <input type="text" class="form-control ic-control complaint-field"
                                       name="categories[0][issue_category]" placeholder="e.g. Accounts"
                                       maxlength="255" required>
                            </div>
                            <div class="mb-0">
                                <label class="ic-form-label">Description<span class="ic-req">*</span></label>
                                <textarea class="form-control ic-control description-field" rows="3"
                                          name="categories[0][description]"
                                          placeholder="e.g. Add Description...." required></textarea>
                            </div>
                            <div class="ic-field-actions">
                                <button type="button" class="ic-field-btn ic-field-btn--remove remove-field-btn"
                                        title="Remove this category" aria-label="Remove this category">
                                    <i class="bi bi-dash-lg" aria-hidden="true"></i>
                                </button>
                                <button type="button" class="ic-field-btn ic-field-btn--add add-field-btn"
                                        title="Add another category" aria-label="Add another category">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer ic-modal-footer">
                    <button type="button" class="btn ic-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn ic-btn-submit">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ic-modal border-0 shadow">
            <form id="editCategoryForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-header ic-modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                {{-- Same card / label / control language as Add — only the repeat
                     controls are absent, because Edit works on one row. --}}
                <div class="modal-body ic-modal-body">
                    <div class="ic-field-card">
                        <div class="mb-3">
                            <label for="edit_issue_category" class="ic-form-label">Complaint<span class="ic-req">*</span></label>
                            <input type="text" class="form-control ic-control" id="edit_issue_category" name="issue_category"
                                   placeholder="e.g. Accounts" maxlength="255" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description" class="ic-form-label">Description</label>
                            <textarea class="form-control ic-control" id="edit_description" name="description" rows="3"
                                      placeholder="e.g. Lorem Ipsum dolor sit amet"></textarea>
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
                    <button type="submit" class="btn ic-btn-submit">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="icColumnVisibilityModal" tabindex="-1" aria-labelledby="icColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="icColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="issueCatColumnToggleGrid"></div>
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
   so the browser only ever holds the page it is showing. */
(function() {
    'use strict';
    document.addEventListener('DOMContentLoaded', function() {
        var $ = window.jQuery;
        if (!$ || !$.fn.DataTable) return;
        var $table = $('#categoriesTable');
        if (!$table.length || $.fn.DataTable.isDataTable($table)) return;

        var dt = $table.DataTable({
            serverSide: true,
            /* datatable-global-ui.js turns DataTables' native ordering OFF for
               server-side tables unless this opt-in is present, and sorts only the
               rows already loaded instead. We want ORDER BY over the whole set. */
            sargamServerOrder: true,
            processing: true,
            ajax: { url: '{{ route('admin.issue-categories.data') }}' },
            order: [[1, 'asc']],                 // Category A→Z
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
            searchDelay: 400,
            columns: [
                // DT_RowIndex is numbered by the server for the returned page.
                { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'category_name', name: 'category_name' },
                { data: 'description', name: 'description' },
                { data: 'sub_categories', name: 'sub_categories', searchable: false },
                { data: 'status', name: 'status', searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ],
            language: {
                processing: 'Loading…',
                search: 'Search categories:',
                lengthMenu: 'Show _MENU_ entries',
                info: 'Showing _START_ to _END_ of _TOTAL_ categories',
                infoEmpty: 'No categories',
                infoFiltered: '(filtered from _MAX_ total)',
                zeroRecords: 'No matching categories found',
                emptyTable: 'No Categories Found — get started by creating your first complaint category.',
                paginate: { first: 'First', last: 'Last', next: 'Next', previous: 'Previous' }
            },
            drawCallback: function() {
                if (typeof window.adjustAllDataTables === 'function') {
                    try { window.adjustAllDataTables(); } catch (e) {}
                }
            }
        });

        /* A status toggle changes a value the server rendered into this row, so
           re-draw the current page instead of leaving the cell stale. */
        $(document).ajaxSuccess(function (event, xhr, settings) {
            var url = (settings && settings.url) ? settings.url : '';
            if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) return;
            setTimeout(function () { dt.ajax.reload(null, false); }, 250);
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

    /* ── Add modal: repeatable Complaint / Description cards ─────────────── */

    /* Single source of truth for the repeat controls and the field names, run
       after every add/remove. Deriving both from the current DOM (rather than
       nudging the previous/next card) means a clone can't inherit stale state. */
    function syncFieldCards() {
        var $groups = $('#categoryFieldsContainer .category-field-group');
        var last = $groups.length - 1;

        $groups.each(function (index) {
            $(this).attr('data-index', index);
            $(this).find('.complaint-field').attr('name', 'categories[' + index + '][issue_category]');
            $(this).find('.description-field').attr('name', 'categories[' + index + '][description]');

            // Remove only once there is something left to keep; add only on the last card.
            $(this).find('.remove-field-btn').toggle($groups.length > 1);
            $(this).find('.add-field-btn').toggle(index === last);
        });
    }

    $(document).on('click', '.add-field-btn', function () {
        var container = $('#categoryFieldsContainer');
        var newGroup = container.find('.category-field-group').first().clone();

        newGroup.find('.complaint-field, .description-field').val('').prop('disabled', false);
        newGroup.find('.is-invalid').removeClass('is-invalid');
        newGroup.find('.invalid-feedback').remove();

        container.append(newGroup);
        syncFieldCards();
        newGroup.find('.complaint-field').trigger('focus');
    });

    $(document).on('click', '.remove-field-btn', function () {
        var container = $('#categoryFieldsContainer');
        if (container.find('.category-field-group').length <= 1) { return; }

        $(this).closest('.category-field-group').fadeOut(200, function () {
            $(this).remove();
            syncFieldCards();
        });
    });

    $('#addCategoryModal').on('hidden.bs.modal', function () {
        var container = $('#categoryFieldsContainer');
        container.find('.category-field-group:not(:first)').remove();
        container.find('input, textarea').val('').prop('disabled', false);
        container.find('.is-invalid').removeClass('is-invalid');
        container.find('.invalid-feedback').remove();
        syncFieldCards();
    });

    syncFieldCards();

    $('#addCategoryModal form').on('submit', function (e) {
        var form = $(this);
        var isValid = true;
        var hasData = false;

        form.find('.is-invalid').removeClass('is-invalid');
        form.find('.invalid-feedback').remove();

        $('#categoryFieldsContainer .category-field-group').each(function () {
            var complaint = $(this).find('.complaint-field').val().trim();
            var description = $(this).find('.description-field').val().trim();
            if (!complaint && !description) { return; }

            hasData = true;

            if (!complaint) {
                isValid = false;
                var $complaint = $(this).find('.complaint-field').addClass('is-invalid');
                if (!$complaint.next('.invalid-feedback').length) {
                    $complaint.after('<div class="invalid-feedback">Complaint field is required.</div>');
                }
            }
            if (!description) {
                isValid = false;
                var $description = $(this).find('.description-field').addClass('is-invalid');
                if (!$description.next('.invalid-feedback').length) {
                    $description.after('<div class="invalid-feedback">Description field is required.</div>');
                }
            }
        });

        if (!hasData || !isValid) {
            e.preventDefault();
            Swal.fire({
                icon: 'error',
                title: hasData ? 'Validation Error' : 'No Data',
                text: hasData
                    ? 'Please fill all required fields for each category entry.'
                    : 'Please add at least one category entry.',
                confirmButtonColor: '#004384'
            });
            return false;
        }

        // Drop half-filled groups so the store() loop never receives empty rows.
        $('#categoryFieldsContainer .category-field-group').each(function () {
            var complaint = $(this).find('.complaint-field').val().trim();
            var description = $(this).find('.description-field').val().trim();
            if (!complaint || !description) {
                // textarea too — a disabled control is not submitted.
                $(this).find('input, textarea').prop('disabled', true);
            }
        });
    });
});
</script>
@endpush
