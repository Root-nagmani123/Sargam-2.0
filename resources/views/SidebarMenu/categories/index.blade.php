@extends('admin.layouts.master')

@section('title', 'Sidebar Categories')

@push('styles')
{{-- Shared Sidebar Menu Builder chrome — the module stylesheet the Menu Groups
     and Menus grids will use too, so the screens cannot drift apart.
     See docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/sidebar-menu-admin.css') }}?v={{ @filemtime(public_path('css/sidebar-menu-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid sbm-page">
    <x-breadcrum title="Sidebar Categories" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="sbmAddBtn">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Category</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Exports — ABOVE the card, per §1. Nothing here filters by status, so the
         row keeps its place with the buttons alone on the right. --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">

        <div class="d-flex flex-wrap align-items-end gap-2 sbm-secondary-actions">
            {{-- ?q / ?cols are stamped on by sbmUpdateExportLinks(), so a download
                 carries the same search and columns as the grid. --}}
            <a href="{{ route('sidebar.categories.export', ['format' => 'csv']) }}"
               id="sbmDownloadLink"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </a>
            <a href="{{ route('sidebar.categories.export', ['format' => 'print']) }}"
               id="sbmPrintLink" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: columns + search on the right (§2). This grid has no
                 filter selects, so there is nothing on the left to reset. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4
                        programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="sbmBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#sbmColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="sbmDtSearch" class="programme-dt-search" data-dt-search-for="sidebarCategoriesTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and paging are all SQL, so only the
                         page on screen ever reaches the browser. No `dom`/colVis
                         options here — the global script owns that chrome. --}}
                    <table id="sidebarCategoriesTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">Sr No.</th>
                                <th scope="col">Name</th>
                                <th scope="col">Slug</th>
                                <th scope="col">Icon</th>
                                <th scope="col">Order</th>
                                <th scope="col">Created</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates, datatable-global-ui.js
                     fills this in with the pager and "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="sidebarCategoriesTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit — one modal, two modes. Same header, field card, labels and
     footer pair either way; only the title and the submit caption change (§3c). --}}
<div class="modal fade" id="CategoryModal" tabindex="-1" aria-labelledby="CategoryModalLabel"
     data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content sbm-modal border-0 shadow">
            <form id="categoryForm" action="{{ route('sidebar.categories.store') }}" method="post" novalidate>
                @csrf
                <input type="hidden" name="id" id="categoryId">

                <div class="modal-header sbm-modal-header">
                    <div>
                        <h5 class="modal-title" id="CategoryModalLabel">Add Category</h5>
                        <p class="sbm-modal-sub" id="CategoryModalSub">Create a new top-bar category.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body sbm-modal-body">
                    {{-- Two columns: the grid gap owns the spacing, so the cells carry
                         no margin of their own (.sbm-form-grid > .form-group). --}}
                    <div class="sbm-field-card sbm-form-grid">
                        <div class="form-group">
                            <label class="sbm-form-label" for="name">Name<span class="sbm-req">*</span></label>
                            <input type="text" class="form-control sbm-control" name="name" id="name"
                                   placeholder="e.g. Training Resources" value="{{ old('name') }}"
                                   autocomplete="off" maxlength="100" required aria-required="true">
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="slug">Slug<span class="sbm-req">*</span></label>
                            <input type="text" class="form-control sbm-control font-monospace" name="slug" id="slug"
                                   placeholder="e.g. training-resources" value="{{ old('slug') }}"
                                   readonly required aria-required="true" aria-describedby="slug-help"
                                   autocomplete="off" maxlength="100">
                            <p id="slug-help" class="sbm-form-help">
                                Generated automatically from the name (lowercase, hyphenated).
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="order">Display order</label>
                            <input type="number" class="form-control sbm-control" name="order" id="order"
                                   placeholder="0" value="{{ old('order') }}" inputmode="numeric" min="0"
                                   aria-describedby="order-help">
                            <p id="order-help" class="sbm-form-help">Lower numbers appear first (optional).</p>
                        </div>

                        {{-- Status is editable here as well as from the row switch — a
                             category created Inactive would otherwise need a second trip. --}}
                        <div class="form-group">
                            <label class="sbm-form-label" for="is_active">Status<span class="sbm-req">*</span></label>
                            <select class="form-select sbm-control" name="is_active" id="is_active"
                                    required aria-required="true">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer sbm-modal-footer">
                    <button type="button" class="btn sbm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sbm-btn-submit" id="SubmitCategoryForm">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="sbmColumnVisibilityModal" tabindex="-1" aria-labelledby="sbmColumnVisibilityLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="sbmColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="sidebarCatColumnToggleGrid"></div>
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

    var STORE_URL = "{{ route('sidebar.categories.store') }}";
    var UPDATE_BASE = "{{ url('sidebar/categories') }}";

    /* ── DataTable (server-side) ─────────────────────────────────────────────
       `sargamServerOrder` keeps ordering on the server, so clicking a header
       re-sorts the WHOLE set rather than shuffling the visible page. The default
       sort is the Display order column (index 4) — the service's query is left
       unordered on purpose so a header click isn't overruled by it. ── */
    var dt = $('#sidebarCategoriesTable').DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        searchDelay: 400,          // search as you type, one query per pause
        // No default sort — rows arrive in the database's own order until a
        // header is clicked. Use [[4, 'asc']] to open on Display order instead.
        order: [],
        /* footer.blade.php:80 turns the Responsive extension on globally. It
           deals with a table wider than its box by HIDING columns (on a narrow
           screen that takes the Action column away) and swaps in its own +/−
           child-row chrome, which is not this design's. The panel's
           .table-responsive scrolls horizontally instead — §3. */
        responsive: false,
        ajax: {
            url: "{{ route('sidebar.categories.index') }}"
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'slug', name: 'slug' },
            { data: 'icon', name: 'icon', className: 'text-center' },
            { data: 'order', name: 'order', className: 'text-center' },
            { data: 'created_at', name: 'created_at', className: 'text-nowrap' },
            { data: 'status', name: 'is_active', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="sbm-empty">' +
                '<i class="bi bi-folder-x d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Categories Found</h6>' +
                '<p class="mb-0 small">Get started by adding your first sidebar category.</p>' +
                '</div>',
            zeroRecords: '<div class="sbm-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Categories Found</h6>' +
                '<p class="mb-0 small">No category matches your search.</p>' +
                '</div>'
        }
    });

    /* ── Column visibility (DataTables column API) ────────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one.
       An unknown label is simply ignored, so a renamed column comes back
       visible — the safe direction to fail in. ── */
    var COL_KEY = 'sidebarCatGrid:hiddenColumns:v1';

    /* Header index -> the export key the server understands
       (SidebarCategoryService::exportColumnDefs()). Positional: '' marks a column
       that is not in the export at all — here, Action.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var SBM_EXPORT_COLUMN_KEYS = ['sno', 'name', 'slug', 'icon', 'order', 'created_at', 'status', ''];
    var SBM_EXPORT_COL_COUNT = SBM_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep Download and Print carrying exactly the columns still on screen, plus
       the search term currently applied to the grid. */
    function sbmUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = SBM_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var term = dt.search() || '';

        ['sbmDownloadLink', 'sbmPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('cols');
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length !== SBM_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    // Search-as-you-type has to re-stamp the links, not just redraw the grid.
    dt.on('search.dt', sbmUpdateExportLinks);

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
        var $grid = $('#sidebarCatColumnToggleGrid');
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

            var inputId = 'sbmcolvis_' + index;
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
                sbmUpdateExportLinks();
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
    sbmUpdateExportLinks();

    /* ── Status switch ───────────────────────────────────────────────────────
       This grid has its own endpoint rather than the generic table/column one
       custom.js binds `.status-toggle` to, so the confirm + AJAX live here. The
       badge and the switch are in different columns, so redraw from the server
       on success rather than hand-mirroring them. ── */
    $(document).on('change', '.sidebar-category-status-toggle', function () {
        var $checkbox = $(this);
        var id = $checkbox.data('id');
        var value = $checkbox.is(':checked') ? 1 : 0;
        var actionText = value === 1 ? 'activate' : 'deactivate';
        var name = $checkbox.data('name') || 'this category';

        function revert() { $checkbox.prop('checked', value !== 1); }

        function send() {
            $.ajax({
                url: "{{ route('sidebar.categories.status', ':id') }}".replace(':id', id),
                type: 'GET',
                data: { _token: "{{ csrf_token() }}", is_active: value },
                success: function (response) {
                    if (response && response.success) {
                        if (typeof toastr !== 'undefined') { toastr.success(response.message); }
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error((response && response.message) || 'Something went wrong');
                    }
                    // Current page, not page 1 — keeps the user where they were.
                    dt.ajax.reload(null, false);
                },
                error: function () {
                    if (typeof toastr !== 'undefined') { toastr.error('Something went wrong'); }
                    revert();
                }
            });
        }

        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            if (window.confirm('Are you sure you want to ' + actionText + ' "' + name + '"?')) { send(); }
            else { revert(); }
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to ' + actionText + ' "' + name + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, ' + actionText,
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#004384',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) { send(); } else { revert(); }
        });
    });

    /* ── Delete: confirm before submitting ───────────────────────────────── */
    $(document).on('submit', '.sbm-delete-form', function (e) {
        var form = this;
        if ($(form).data('confirmed')) { return; }
        e.preventDefault();

        var name = $(form).find('.sbm-act--del').data('name') || 'this category';

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

    /* ── Add / Edit modal ────────────────────────────────────────────────────
       One modal, two modes. Everything that differs between them is set here:
       the title, the submit caption, the form action and the _method spoof. ── */
    function openCategoryModal(data) {
        var $form = $('#categoryForm');

        $form.find('input[name="_method"]').remove();
        if ($form.data('validator')) { $form.validate().resetForm(); }
        $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $form.find('.invalid-feedback').remove();
        $('#slug').removeData('manual');
        $('#SubmitCategoryForm').prop('disabled', false).removeAttr('aria-busy');

        if (data) {
            $('#CategoryModalLabel').text('Edit Category');
            $('#CategoryModalSub').text('Update “' + (data.name || 'this category') + '”.');
            $('#SubmitCategoryForm').text('Update Category');
            $('#categoryId').val(data.id);
            $('#name').val(data.name);
            $('#slug').val(data.slug);
            $('#icon').val(data.icon);
            $('#order').val(data.order);
            $('#is_active').val(String(data.status) === '1' ? '1' : '0');
            $form.attr('action', UPDATE_BASE + '/' + data.id)
                 .append('<input type="hidden" name="_method" value="PUT">');
        } else {
            $('#CategoryModalLabel').text('Add Category');
            $('#CategoryModalSub').text('Create a new top-bar category.');
            $('#SubmitCategoryForm').text('Add Category');
            $form[0].reset();
            $('#categoryId').val('');
            $form.attr('action', STORE_URL);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('CategoryModal')).show();
    }

    $('#sbmAddBtn').on('click', function () { openCategoryModal(null); });

    $(document).on('click', '.sbm-edit-btn', function () {
        var $btn = $(this);
        openCategoryModal({
            id: $btn.attr('data-id'),
            name: $btn.attr('data-name'),
            slug: $btn.attr('data-slug'),
            icon: $btn.attr('data-icon'),
            order: $btn.attr('data-order'),
            status: $btn.attr('data-status')
        });
    });

    // Slug mirrors the name until the user types their own.
    $('#name').on('keyup', function () {
        if ($('#slug').data('manual') === true) { return; }
        $('#slug').val(
            $(this).val().toLowerCase().trim()
                .replace(/ /g, '-')
                .replace(/[^\w-]+/g, '')
                .replace(/--+/g, '-')
        );
    });

    $('#slug').on('keyup', function () { $(this).data('manual', true); });

    /* ── Validation ─────────────────────────────────────────────────────────
       `novalidate` on the form leaves enforcement to jquery-validate, which can
       show a message next to the field instead of a native bubble. ── */
    $.validator.addMethod('nameRegex', function (value, element) {
        return this.optional(element) || /^[A-Za-z .'-]+$/.test(value);
    }, "Name can only contain letters, spaces, ., ' and -.");

    $.validator.addMethod('slugRegex', function (value, element) {
        return this.optional(element) || /^[a-z0-9-]+$/.test(value);
    }, 'Slug can only contain lowercase letters, numbers and hyphens.');

    $('#categoryForm').validate({
        ignore: '.ignore',
        rules: {
            name: { required: true, minlength: 2, maxlength: 100, nameRegex: true },
            slug: { required: true, minlength: 2, maxlength: 100, slugRegex: true },
            icon: { maxlength: 100 },
            order: { required: false, digits: true },
            is_active: { required: true }
        },
        messages: {
            name: {
                required: 'Please enter category name',
                minlength: 'Name must be at least 2 characters',
                maxlength: 'Name must be less than 100 characters'
            },
            slug: {
                required: 'Slug is required',
                minlength: 'Slug must be at least 2 characters',
                maxlength: 'Slug must be less than 100 characters'
            },
            icon: { maxlength: 'Icon must be less than 100 characters' },
            order: { digits: 'Order must be a number' },
            is_active: { required: 'Please select status' }
        },
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid');
        },
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            element.closest('.form-group').append(error);
        },
        submitHandler: function (form) {
            var $btn = $('#SubmitCategoryForm');
            $btn.prop('disabled', true).attr('aria-busy', 'true').html(
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                '<span>Processing…</span>'
            );
            form.submit();
        }
    });
});
</script>
@endpush
