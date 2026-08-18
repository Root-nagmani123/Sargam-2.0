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
        {{-- ?q= and ?cols= are stamped on by icUpdateExportCols(), so a download
             carries the same search term and columns the grid is showing. --}}
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

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and paging are all SQL, so only the
                         page on screen ever reaches the browser and the grid does not
                         grow with the table. The global enhancer supplies the search
                         slot and footer. --}}
                    <table id="issueCategoriesTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
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
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates, datatable-global-ui.js fills
                     this in with the pager and "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="issueCategoriesTable"></div>
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
$(function () {
    'use strict';

    /* ── DataTable (server-side) ─────────────────────────────────────────────
       Search, sort, paging and the "Showing N of M items" footer are all
       DataTables', and every one of them is answered by the server: a draw
       fetches only the page being shown. `sargamServerOrder` keeps ordering on
       the server too, so clicking a header re-sorts the WHOLE set rather than
       shuffling the visible page. No `dom` or colVis options here — the global
       script owns that. ── */
    var $table = $('#issueCategoriesTable');

    var dt = $table.DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        // 400ms after the last keystroke — search as you type, one query per pause.
        searchDelay: 400,
        order: [[1, 'asc']],                       // Category A→Z, matching the old default
        ajax: {
            url: '{{ route('admin.issue-categories.data') }}'
        },
        /* `name` is the sort key the controller whitelists (SORTABLE_COLUMNS);
           an empty one means "not sortable in SQL" and the server falls back to
           its default order. */
        columns: [
            { data: 'sno', name: '', orderable: false, searchable: false, className: 'text-center' },
            { data: 'category', name: 'category' },
            { data: 'description', name: 'description' },
            { data: 'sub_categories', name: 'sub_categories' },
            { data: 'status', name: 'status' },
            { data: 'action', name: '', orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="ic-empty">' +
                '<i class="bi bi-folder-x d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Categories Found</h6>' +
                '<p class="mb-0 small">Get started by creating your first complaint category.</p>' +
                '</div>',
            zeroRecords: '<div class="ic-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Categories Found</h6>' +
                '<p class="mb-0 small">No category matches your search.</p>' +
                '</div>'
        }
    });

    /* ── Column visibility (DataTables column API) ────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one.
       An unknown label is simply ignored, so a renamed column comes back
       visible — the safe direction to fail in. ── */
    var COL_KEY = 'issueCatGrid:hiddenColumns:v2';

    /* Header index -> the export key the server understands
       (IssueCategoryController::exportColumnDefs()). Positional: '' marks a
       column that is not in the export at all — here, Action.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var IC_EXPORT_COLUMN_KEYS = ['sno', 'category', 'description', 'sub_categories', 'status', ''];
    var IC_EXPORT_COL_COUNT = IC_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep Download and Print carrying exactly the columns still on screen, plus
       the search term currently applied to it. */
    function icUpdateExportCols() {
        var keys = [];
        dt.columns().every(function () {
            var key = IC_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        // The term lives in DataTables, which sends it to the grid feed as
        // search[value]; the export reads ?q=. Without carrying it the download
        // returns every row and its header cannot name the filter applied.
        var term = dt.search() || '';

        ['icDownloadLink', 'icExcelLink', 'icPdfLink', 'icPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');
            params.delete('q');
            if (term !== '') { params.set('q', term); }
            params.delete('cols');
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length !== IC_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }
            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    // Search-as-you-type has to re-stamp the links, not just redraw the grid.
    dt.on('search.dt', icUpdateExportCols);

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
        var $grid = $('#issueCatColumnToggleGrid');
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

            var inputId = 'iccolvis_' + index;
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
                icUpdateExportCols();
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
    // Stamp the saved column state onto the export links on first paint too —
    // otherwise a preference restored from localStorage wouldn't reach the server
    // until the user opened the modal and toggled something.
    icUpdateExportCols();

    /* ── Status toggle: repaint the row (badge + caption + delete guard) ──
       custom.js does the AJAX; the badge and the switch live in different
       columns, so redraw from the server rather than hand-mirroring them.
       Reloading the current page (not page 1) keeps the user where they were. ── */
    $(document).ajaxSuccess(function (event, xhr, settings) {
        var url = (settings && settings.url) ? settings.url : '';
        if (url.indexOf('toggle-status') === -1 && url.indexOf('toggleStatus') === -1) { return; }
        setTimeout(function () { dt.ajax.reload(null, false); }, 600);
    });

    /* ── Delete: confirm before submitting ───────────────────────────────── */
    $(document).on('submit', '.ic-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();

        var name = $(form).find('.ic-act--del').data('name') || 'this category';

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
    $(document).on('click', '.ic-edit-btn', function () {
        var $btn = $(this);
        $('#edit_issue_category').val($btn.data('name'));
        $('#edit_description').val($btn.data('description') || '');
        $('#edit_status').val(String($btn.data('status')) === '1' ? '1' : '0');
        $('#editCategoryForm').attr('action', "{{ url('admin/issue-categories') }}/" + $btn.data('id'));

        bootstrap.Modal.getOrCreateInstance(document.getElementById('editCategoryModal')).show();
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
