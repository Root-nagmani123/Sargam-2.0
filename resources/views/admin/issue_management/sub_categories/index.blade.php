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

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- No data-sargam-dt-ui opt-out: DataTables paginates this grid now. --}}
                    <table id="issueSubCategoriesTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S. No.</th>
                                <th scope="col">Category</th>
                                <th scope="col">Sub-Categories Name</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subCategories as $subCategory)
                                @php $isActive = (int) $subCategory->status === 1; @endphp
                                <tr>
                                    {{-- Renumbered on every draw (see the JS). --}}
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $subCategory->category->issue_category ?? '—' }}</td>
                                    <td>{{ $subCategory->issue_sub_category }}</td>
                                    <td data-order="{{ (int) $subCategory->status }}">
                                        <span class="status-pill badge rounded-1 {{ $isActive ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                            {{ $isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="ic-act-group" role="group" aria-label="Row actions">
                                            <button type="button" class="ic-act ic-act--edit isc-edit-btn" aria-label="Edit sub-category"
                                                    data-id="{{ $subCategory->pk }}"
                                                    data-category="{{ $subCategory->issue_category_master_pk }}"
                                                    data-name="{{ $subCategory->issue_sub_category }}"
                                                    data-status="{{ (int) $subCategory->status }}">
                                                <span class="ic-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                                <span class="ic-act__label">Edit</span>
                                            </button>

                                            {{-- No .form-check/.form-switch wrapper — see the shared stylesheet. --}}
                                            <label class="ic-act ic-act--toggle">
                                                <span class="ic-act__icon">
                                                    <input class="form-check-input status-toggle" type="checkbox" role="switch"
                                                           data-table="issue_sub_category_master" data-column="status"
                                                           data-id="{{ $subCategory->pk }}" {{ $isActive ? 'checked' : '' }}>
                                                </span>
                                                <span class="ic-act__label">{{ $isActive ? 'Activate' : 'Deactivate' }}</span>
                                            </label>

                                            @if($isActive)
                                                {{-- destroy() refuses to delete an active sub-category — mirror that guard. --}}
                                                <span class="ic-act ic-act--del is-disabled" aria-disabled="true"
                                                      title="Deactivate this sub-category before deleting it">
                                                    <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                    <span class="ic-act__label">Delete</span>
                                                </span>
                                            @else
                                                <form action="{{ route('admin.issue-sub-categories.destroy', $subCategory->pk) }}"
                                                      method="POST" class="ic-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="ic-act ic-act--del" aria-label="Delete sub-category"
                                                            data-name="{{ $subCategory->issue_sub_category }}">
                                                        <span class="ic-act__icon"><i class="bi bi-trash3" aria-hidden="true"></i></span>
                                                        <span class="ic-act__label">Delete</span>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates; the global UI fills this in. --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="issueSubCategoriesTable"></div>
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
$(function () {
    'use strict';

    /* ── Toolbar: category filter submits on change ──────────────────────── */
    $('#iscCategoryFilter').on('change', function () {
        $('#iscFilterForm').trigger('submit');
    });

    /* ── DataTable ───────────────────────────────────────────────────────────
       Search, sort, paging and the footer are DataTables' now;
       datatable-global-ui.js supplies the defaults and moves the filter/pager
       into the toolbar and footer slots. ── */
    var $table = $('#issueSubCategoriesTable');

    var dt = $table.DataTable({
        order: [[1, 'asc']],
        columnDefs: [
            { targets: 0, orderable: false, searchable: false, className: 'text-center' },
            { targets: -1, orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="ic-empty">' +
                '<i class="bi bi-diagram-3 d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Sub-Categories Found</h6>' +
                '<p class="mb-0 small">Get started by creating your first sub-category.</p>' +
                '</div>',
            zeroRecords: '<div class="ic-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Sub-Categories Found</h6>' +
                '<p class="mb-0 small">No sub-category matches your search.</p>' +
                '</div>'
        }
    });

    // S. No. follows what is on screen, not the original row order.
    function renumberSerial() {
        var start = dt.page.info().start;
        dt.column(0, { search: 'applied', order: 'applied', page: 'current' })
          .nodes()
          .each(function (cell, i) { cell.innerHTML = start + i + 1; });
    }
    dt.on('draw.dt', renumberSerial);
    renumberSerial();

    /* ── Column visibility (DataTables column API) ────────────────────────
       Stored by LABEL, not index — an index points at a different column the
       moment one is added, silently hiding the wrong one. ── */
    var COL_KEY = 'issueSubCatGrid:hiddenColumns:v2';

    /* Header index -> export key (IssueSubCategoryController::exportColumnDefs()).
       Positional: '' marks a column that is not in the export (Action).
       ⚠️ Adding a table column means adding an entry here too. */
    var ISC_EXPORT_COLUMN_KEYS = ['sno', 'category', 'sub_category', 'status', ''];
    var ISC_EXPORT_COL_COUNT = ISC_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep every export link carrying exactly the columns still on screen. */
    function iscUpdateExportCols() {
        var keys = [];
        dt.columns().every(function () {
            var key = ISC_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        ['iscDownloadLink', 'iscExcelLink', 'iscPdfLink', 'iscPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');
            params.delete('cols');
            // Omit ?cols= while nothing is hidden — the server reads that as "all".
            if (keys.length !== ISC_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }
            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    function getHiddenCols() {
        try {
            var parsed = JSON.parse(localStorage.getItem(COL_KEY) || '[]');
            return Array.isArray(parsed) ? parsed : [];
        } catch (e) { return []; }
    }

    function persistHiddenCols(cols) {
        try { localStorage.setItem(COL_KEY, JSON.stringify(cols)); } catch (e) { /* noop */ }
    }

    function buildColumnToggles() {
        var $grid = $('#issueSubCatColumnToggleGrid');
        var hidden = getHiddenCols();

        dt.columns().every(function () {
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (title) { this.visible(hidden.indexOf(title) === -1, false); }
        });
        dt.columns.adjust();

        if (!$grid.length) { return; }
        $grid.empty();

        dt.columns().every(function () {
            var index = this.index();
            var title = $(this.header()).text().replace(/\s+/g, ' ').trim();
            if (!title) { return; }

            var inputId = 'isccolvis_' + index;
            var $checkbox = $('<input type="checkbox" class="form-check-input m-0">')
                .attr('id', inputId)
                .prop('checked', hidden.indexOf(title) === -1);

            $checkbox.on('change', function () {
                var cols = getHiddenCols();
                var pos = cols.indexOf(title);
                if (this.checked) {
                    if (pos !== -1) { cols.splice(pos, 1); }
                } else if (pos === -1) {
                    cols.push(title);
                }
                persistHiddenCols(cols);
                dt.column(index).visible(this.checked, false);
                dt.columns.adjust();
                renumberSerial();
                iscUpdateExportCols();
            });

            $('<div class="col-12 col-sm-6 col-md-4"></div>').append(
                $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>')
                    .attr('for', inputId)
                    .append($checkbox)
                    .append($('<span></span>').text(title))
            ).appendTo($grid);
        });
    }

    buildColumnToggles();
    // Stamp the restored column state onto the export links on first paint too.
    iscUpdateExportCols();


    /* ── Status toggle: repaint the row (badge + caption + delete guard) ──
       custom.js does the AJAX; the badge and the switch live in different
       columns, so reload rather than hand-mirroring them. ── */
    $(document).ajaxSuccess(function (event, xhr, settings) {
        var url = (settings && settings.url) ? settings.url : '';
        if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) { return; }
        setTimeout(function () { window.location.reload(); }, 600);
    });

    /* ── Delete: confirm before submitting ───────────────────────────────── */
    $(document).on('submit', '.ic-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();

        var name = $(form).find('.ic-act--del').data('name') || 'this sub-category';

        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            if (window.confirm('Delete "' + name + '"? This cannot be undone.')) {
                $(form).data('confirmed', true);
                form.submit();
            }
            return;
        }

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
                $(form).data('confirmed', true);
                form.submit();
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
