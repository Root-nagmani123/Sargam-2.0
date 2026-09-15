@extends('admin.layouts.master')

@section('title', 'Role & Permission')

@push('styles')
{{-- Module stylesheet for Roles & Permissions. Not roles-admin.css — that one
     belongs to the separate User Management Roles screen. See the header note in
     the file and docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/roles-permissions-admin.css') }}?v={{ @filemtime(public_path('css/roles-permissions-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid rp-page">
    <x-breadcrum title="Role & Permission" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="rpAddBtn">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Role</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Exports — ABOVE the card, per §1. Nothing here filters by status, so the
         row keeps its place with the buttons alone on the right. --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap align-items-end gap-2 rp-secondary-actions">
            {{-- ?q / ?cols are stamped on by rpUpdateExportLinks(), so a download
                 carries the same search and columns as the grid. --}}
            <div class="dropdown">
                <button type="button"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        id="rpDownloadMenuBtn" data-bs-toggle="dropdown" aria-expanded="false"
                        title="Download this list">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end programme-dt-download-menu"
                    aria-labelledby="rpDownloadMenuBtn">
                    <li>
                        <a class="dropdown-item" id="rpCsvLink"
                           href="{{ route('roles.export', ['format' => 'csv']) }}">
                            <i class="bi bi-filetype-csv" aria-hidden="true"></i><span>CSV</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" id="rpExcelLink"
                           href="{{ route('roles.export', ['format' => 'excel']) }}">
                            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i><span>Excel</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" id="rpPdfLink"
                           href="{{ route('roles.export', ['format' => 'pdf']) }}">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><span>PDF</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Print stays OUTSIDE the dropdown: it opens a sheet rather than
                 saving a file, so it is not one of the download formats. --}}
            <a href="{{ route('roles.export', ['format' => 'print']) }}"
               id="rpPrintLink" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: columns + search on the right (§2). Roles have no status
                 or category, so there is nothing on the left to reset. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-end gap-3 mb-4
                        programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="rpBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#rpColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="rpDtSearch" class="programme-dt-search" data-dt-search-for="rolesTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and paging are all SQL, so only the
                         page on screen ever reaches the browser. No `dom`/colVis
                         options here — the global script owns that chrome. --}}
                    <table id="rolesTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                {{-- text-center on the headers whose columns are
                                     given className 'text-center' below. --}}
                                <th scope="col" class="text-center">Sr No.</th>
                                <th scope="col">Role</th>
                                <th scope="col" class="text-center">Permissions</th>
                                <th scope="col">Created</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates, datatable-global-ui.js
                     fills this in with the pager and "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="rolesTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit — one modal, two modes. Same header, field card and footer pair
     either way; only the title and the submit caption change (§3c). --}}
<div class="modal fade" id="RoleModal" tabindex="-1" aria-labelledby="RoleModalLabel"
     data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rp-modal border-0 shadow">
            <form id="roleForm" action="{{ route('roles.store') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="id" id="roleId">

                <div class="modal-header rp-modal-header">
                    <div>
                        <h5 class="modal-title" id="RoleModalLabel">Add Role</h5>
                        <p class="rp-modal-sub" id="RoleModalSub">Create a new role.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body rp-modal-body">
                    {{-- One field, so no two-column grid here — it would leave half
                         the card empty. --}}
                    <div class="rp-field-card">
                        <div class="form-group">
                            <label class="rp-form-label" for="name">Name<span class="rp-req">*</span></label>
                            <input type="text" class="form-control rp-control" name="name" id="name"
                                   placeholder="e.g. Course Coordinator" value="{{ old('name') }}"
                                   autocomplete="off" maxlength="100">
                            <p class="rp-form-help">
                                Permissions and dashboard cards are assigned from the row actions
                                once the role exists.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer rp-modal-footer">
                    <button type="button" class="btn rp-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn rp-btn-submit" id="SubmitRoleForm">Add Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="rpColumnVisibilityModal" tabindex="-1" aria-labelledby="rpColumnVisibilityLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="rpColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="rolesColumnToggleGrid"></div>
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

    var STORE_URL = "{{ route('roles.store') }}";
    var UPDATE_BASE = "{{ url('roles') }}";

    /* ── DataTable (server-side) ─────────────────────────────────────────────
       `sargamServerOrder` keeps ordering on the server, so clicking a header
       re-sorts the WHOLE set rather than shuffling the visible page. The
       service's query is left unordered on purpose so a header click isn't
       overruled by it. ── */
    var dt = $('#rolesTable').DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        searchDelay: 400,          // search as you type, one query per pause
        order: [[1, 'asc']],       // Role A→Z
        /* footer.blade.php:80 turns the Responsive extension on globally. It
           deals with a table wider than its box by HIDING columns (on a narrow
           screen that takes the Action column away) and swaps in its own +/−
           child-row chrome, which is not this design's. The panel's
           .table-responsive scrolls horizontally instead — §3. */
        responsive: false,
        ajax: {
            url: "{{ route('roles.index') }}"
        },
        /* `permissions_count` is a withCount alias, not a column DataTables can
           hand to the server as an ORDER BY — leave it unsorted rather than
           shipping a caret that errors. */
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'name', name: 'name' },
            { data: 'permissions_count', name: 'permissions_count', orderable: false, searchable: false, className: 'text-center' },
            { data: 'created_at', name: 'created_at', className: 'text-nowrap' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="rp-empty">' +
                '<i class="bi bi-shield-lock d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Roles Found</h6>' +
                '<p class="mb-0 small">Get started by adding your first role.</p>' +
                '</div>',
            zeroRecords: '<div class="rp-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Roles Found</h6>' +
                '<p class="mb-0 small">No role matches your search.</p>' +
                '</div>'
        }
    });

    /* ── Column visibility (DataTables column API) ────────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one.
       An unknown label is simply ignored, so a renamed column comes back
       visible — the safe direction to fail in. ── */
    var COL_KEY = 'rolesGrid:hiddenColumns:v1';

    /* Header index -> the export key the server understands
       (RoleService::exportColumnDefs()). Positional: '' marks a column that is
       not in the export at all — here, Action.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var RP_EXPORT_COLUMN_KEYS = ['sno', 'name', 'permissions_count', 'created_at', ''];
    var RP_EXPORT_COL_COUNT = RP_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep Download and Print carrying exactly the columns still on screen, plus
       the search term currently applied to the grid. */
    function rpUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = RP_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var term = dt.search() || '';

        ['rpCsvLink', 'rpExcelLink', 'rpPdfLink', 'rpPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('cols');
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length !== RP_EXPORT_COL_COUNT) { params.set('cols', keys.join(',')); }

            var qs = params.toString();
            link.href = base + (qs ? '?' + qs : '');
        });
    }

    // Search-as-you-type has to re-stamp the links, not just redraw the grid.
    dt.on('search.dt', rpUpdateExportLinks);

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
        var $grid = $('#rolesColumnToggleGrid');
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

            var inputId = 'rpcolvis_' + index;
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
                rpUpdateExportLinks();
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
    rpUpdateExportLinks();

    /* ── Add / Edit modal ────────────────────────────────────────────────────
       One modal, two modes. Everything that differs between them is set here:
       the title, the submit caption, the form action and the _method spoof. ── */
    function openRoleModal(data) {
        var $form = $('#roleForm');

        $form.find('input[name="_method"]').remove();
        if ($form.data('validator')) { $form.validate().resetForm(); }
        $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $form.find('.invalid-feedback').remove();
        $('#SubmitRoleForm').prop('disabled', false).removeAttr('aria-busy');
        $form[0].reset();

        if (data) {
            $('#RoleModalLabel').text('Edit Role');
            $('#RoleModalSub').text('Update “' + (data.name || 'this role') + '”.');
            $('#SubmitRoleForm').text('Update Role');
            $('#roleId').val(data.id);
            $('#name').val(data.name);
            $form.attr('action', UPDATE_BASE + '/' + data.id)
                 .append('<input type="hidden" name="_method" value="PUT">');
        } else {
            $('#RoleModalLabel').text('Add Role');
            $('#RoleModalSub').text('Create a new role.');
            $('#SubmitRoleForm').text('Add Role');
            $('#roleId').val('');
            $form.attr('action', STORE_URL);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('RoleModal')).show();
    }

    $('#rpAddBtn').on('click', function () { openRoleModal(null); });

    $(document).on('click', '.rp-edit-btn', function () {
        var data = $(this).data('item');
        if (typeof data === 'string') {
            try { data = JSON.parse(data); }
            catch (e) {
                if (typeof toastr !== 'undefined') { toastr.error('Could not load role data for editing'); }
                return;
            }
        }
        openRoleModal(data);
    });

    /* ── Validation ─────────────────────────────────────────────────────────
       `novalidate` on the form leaves enforcement to jquery-validate, which can
       show a message next to the field instead of a native bubble.

       The name rule allows the same characters the controller's `unique:roles`
       check will see; underscores are common in existing role names, so they
       are permitted alongside spaces and the usual punctuation. ── */
    $.validator.addMethod('roleNameRegex', function (value, element) {
        return this.optional(element) || /^[A-Za-z0-9 ._'-]+$/.test(value);
    }, "Role name can only contain letters, numbers, spaces and . _ ' -");

    $('#roleForm').validate({
        ignore: '.ignore',
        rules: {
            name: { required: true, minlength: 2, maxlength: 100, roleNameRegex: true }
        },
        messages: {
            name: {
                required: 'Please enter role name',
                minlength: 'Name must be at least 2 characters',
                maxlength: 'Name must be less than 100 characters'
            }
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
            var $btn = $('#SubmitRoleForm');
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
