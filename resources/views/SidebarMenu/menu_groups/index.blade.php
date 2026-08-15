@extends('admin.layouts.master')

@section('title', 'Sidebar Menu Groups')

@push('styles')
{{-- Shared Sidebar Menu Builder chrome — the same module stylesheet the Sidebar
     Categories grid uses, so the two screens cannot drift apart.
     See docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/sidebar-menu-admin.css') }}?v={{ @filemtime(public_path('css/sidebar-menu-admin.css')) ?: time() }}">
{{-- ⚠️ PIN the version. The unpinned URL now serves Choices 11.x, whose template
     callback is handed the CONFIG object where 10.x hands it `classNames` — so
     every rendered choice came out with an empty class attribute and the icon
     dropdown looked completely empty. 10.2.0 is what the other ~8 views in this
     app pin, so this keeps one version across the codebase. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
<div class="container-fluid sbm-page">
    <x-breadcrum title="Sidebar Menu Groups" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="sbmAddBtn">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Menu Group</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Exports — ABOVE the card, per §1. Nothing here filters by status, so the
         row keeps its place with the buttons alone on the right. --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap align-items-end gap-2 sbm-secondary-actions">
            {{-- ?category_id / ?q / ?cols are stamped on by sbmUpdateExportLinks(),
                 so a download carries the same filter, search and columns as the
                 grid. --}}
            <a href="{{ route('sidebar.menu-groups.export', ['format' => 'csv']) }}"
               id="sbmDownloadLink"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </a>
            <a href="{{ route('sidebar.menu-groups.export', ['format' => 'print']) }}"
               id="sbmPrintLink" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: filters + reset left, columns + search right (§2). --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4
                        programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select id="sbmCategoryFilter" class="form-select" aria-label="Filter by category">
                            <option value="">Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="sbmResetFilters">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="sbmBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#sbmColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="sbmDtSearch" class="programme-dt-search" data-dt-search-for="sidebarMenuGroupsTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and paging are all SQL, so only the
                         page on screen ever reaches the browser. No `dom`/colVis
                         options here — the global script owns that chrome. --}}
                    <table id="sidebarMenuGroupsTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">Sr No.</th>
                                <th scope="col">Category</th>
                                <th scope="col">Name</th>
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
                     data-dt-footer-for="sidebarMenuGroupsTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit — one modal, two modes. Same header, field card, labels and
     footer pair either way; only the title and the submit caption change (§3c). --}}
<div class="modal fade" id="MenuGroupModal" tabindex="-1" aria-labelledby="MenuGroupModalLabel"
     data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content sbm-modal border-0 shadow">
            <form id="menuGroupForm" action="{{ route('sidebar.menu-groups.store') }}" method="POST" novalidate>
                @csrf

                <div class="modal-header sbm-modal-header">
                    <div>
                        <h5 class="modal-title" id="MenuGroupModalLabel">Add Menu Group</h5>
                        <p class="sbm-modal-sub" id="MenuGroupModalSub">Create a new group inside a category.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body sbm-modal-body">
                    {{-- Two columns: the grid gap owns the spacing, so the cells carry
                         no margin of their own (.sbm-form-grid > .form-group). --}}
                    <div class="sbm-field-card sbm-form-grid">
                        <div class="form-group">
                            <label class="sbm-form-label" for="category_id">Category<span class="sbm-req">*</span></label>
                            <select class="w-100 sbm-choices" name="category_id" id="category_id">
                                <option value="">Select Category</option>
                                @forelse ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @empty
                                    <option value="" disabled>No category found</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="name">Name<span class="sbm-req">*</span></label>
                            <input type="text" class="form-control sbm-control" name="name" id="name"
                                   placeholder="e.g. Course Management" value="{{ old('name') }}"
                                   autocomplete="off" maxlength="100">
                        </div>

                        {{-- Full row: the searchable icon list plus its live preview needs
                             the width, and its dropdown would be cramped in half of one. --}}
                        <div class="form-group sbm-form-grid--full">
                            <label class="sbm-form-label" for="icon">Icon<span class="sbm-req">*</span></label>
                            <div class="d-flex align-items-start gap-2 flex-nowrap sbm-icon-row">
                                <span class="sbm-icon-preview" aria-hidden="true">
                                    <i id="iconPreview" class="material-icons material-symbols-rounded">apps</i>
                                </span>
                                <div class="flex-grow-1 sbm-icon-select-col">
                                    {{-- Intentionally EMPTY apart from the placeholder. The ~4,200
                                         Material Symbols names are shipped once as JSON below and
                                         handed to Choices.js at open time; rendering them as
                                         <option>s cost 4,248 elements / 1.06 MB per page load
                                         (docs/new-design-index-page.md §3c). --}}
                                    <select class="w-100 sbm-choices" name="icon" id="icon">
                                        <option value="">Select icon</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="order">Display order</label>
                            <input type="number" class="form-control sbm-control" name="order" id="order"
                                   placeholder="0" value="{{ old('order') }}" inputmode="numeric" min="0"
                                   aria-describedby="order-help">
                            <p id="order-help" class="sbm-form-help">Lower numbers appear first (optional).</p>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="is_active">Status<span class="sbm-req">*</span></label>
                            <select class="form-select sbm-control" name="is_active" id="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer sbm-modal-footer">
                    <button type="button" class="btn sbm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sbm-btn-submit" id="SubmitMenuGroupForm">Add Menu Group</button>
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
                <div class="row g-3" id="sidebarGroupColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- The icon catalogue, once, as data rather than markup. ~4,200 names ≈ 60 KB
     of JSON against ~450 KB of <option> elements. --}}
<script type="application/json" id="sbmIconNames">@json($materialIcons)</script>
@endsection

@push('scripts')
{{-- Pinned to match the stylesheet above — see the note in @push('styles'). --}}
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
$(function () {
    'use strict';

    var STORE_URL = "{{ route('sidebar.menu-groups.store') }}";
    var UPDATE_BASE = "{{ url('sidebar/menu-groups') }}";

    var ICON_NAMES = (function () {
        try { return JSON.parse(document.getElementById('sbmIconNames').textContent) || []; }
        catch (e) { return []; }
    })();

    /* ── DataTable (server-side) ─────────────────────────────────────────────
       `sargamServerOrder` keeps ordering on the server, so clicking a header
       re-sorts the WHOLE set rather than shuffling the visible page. The default
       sort is the Display order column (index 4) — the service's query is left
       unordered on purpose so a header click isn't overruled by it. ── */
    var dt = $('#sidebarMenuGroupsTable').DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        searchDelay: 400,          // search as you type, one query per pause
        order: [[4, 'asc']],       // Display order — the sidebar's own sequence
        /* footer.blade.php:80 turns the Responsive extension on globally. It
           deals with a table wider than its box by HIDING columns (on a narrow
           screen that takes the Action column away) and swaps in its own +/−
           child-row chrome, which is not this design's. The panel's
           .table-responsive scrolls horizontally instead — §3. */
        responsive: false,
        ajax: {
            url: "{{ route('sidebar.menu-groups.index') }}",
            data: function (d) { d.category_id = $('#sbmCategoryFilter').val() || ''; }
        },
        /* `category.name` is the relation column Yajra sorts and searches through
           a join — safe here, this table is tiny (tens of rows). */
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'category_name', name: 'category.name' },
            { data: 'name', name: 'name' },
            { data: 'icon', name: 'icon', className: 'text-center' },
            { data: 'order', name: 'order', className: 'text-center' },
            { data: 'created_at', name: 'created_at', className: 'text-nowrap' },
            { data: 'status', name: 'is_active', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="sbm-empty">' +
                '<i class="bi bi-folder-x d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Menu Groups Found</h6>' +
                '<p class="mb-0 small">Get started by adding your first menu group.</p>' +
                '</div>',
            zeroRecords: '<div class="sbm-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Menu Groups Found</h6>' +
                '<p class="mb-0 small">No menu group matches your filters.</p>' +
                '</div>'
        }
    });

    /* ── Filters ─────────────────────────────────────────────────────────────── */
    $('#sbmCategoryFilter').on('change', function () {
        dt.ajax.reload();
        sbmUpdateExportLinks();
    });

    $('#sbmResetFilters').on('click', function () {
        $('#sbmCategoryFilter').val('');
        dt.search('').ajax.reload();      // clears the search box too
        sbmUpdateExportLinks();
    });

    /* ── Column visibility (DataTables column API) ────────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one.
       An unknown label is simply ignored, so a renamed column comes back
       visible — the safe direction to fail in. ── */
    var COL_KEY = 'sidebarGroupGrid:hiddenColumns:v1';

    /* Header index -> the export key the server understands
       (MenuGroupService::exportColumnDefs()). Positional: '' marks a column that
       is not in the export at all — here, Action.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var SBM_EXPORT_COLUMN_KEYS = ['sno', 'category', 'name', 'icon', 'order', 'created_at', 'status', ''];
    var SBM_EXPORT_COL_COUNT = SBM_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep Download and Print carrying exactly the columns still on screen, plus
       the category filter and search term currently applied to the grid. */
    function sbmUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = SBM_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var term = dt.search() || '';
        var category = $('#sbmCategoryFilter').val() || '';

        ['sbmDownloadLink', 'sbmPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('category_id');
            if (category !== '') { params.set('category_id', category); }

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
        var $grid = $('#sidebarGroupColumnToggleGrid');
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

            var inputId = 'sbmgcolvis_' + index;
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
    $(document).on('change', '.sidebar-menu-group-status-toggle', function () {
        var $checkbox = $(this);
        var id = $checkbox.data('id');
        var value = $checkbox.is(':checked') ? 1 : 0;
        var actionText = value === 1 ? 'activate' : 'deactivate';
        var name = $checkbox.data('name') || 'this menu group';

        function revert() { $checkbox.prop('checked', value !== 1); }

        function send() {
            $.ajax({
                url: "{{ route('sidebar.menu-groups.status', ':id') }}".replace(':id', id),
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

        var name = $(form).find('.sbm-act--del').data('name') || 'this menu group';

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

    /* ══ Choices.js selects ═════════════════════════════════════════════════
       Both live inside the modal, so they are built on `shown.bs.modal` (a
       Choices dropdown created while the modal is hidden measures wrong) and
       torn down on `hidden.bs.modal`. ── */

    function destroyChoices(el) {
        if (el && el._sbmChoices) {
            try { el._sbmChoices.destroy(); } catch (e) { /* noop */ }
            el._sbmChoices = null;
        }
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function classString(value) {
        return Array.isArray(value) ? value.join(' ') : String(value || '');
    }

    /* ⚠️ No `classNames` override here on purpose. Choices 10.x assigns those
       values straight to `className` (and to classList.add for the highlight
       state), so it only accepts single-class STRINGS — an array of Bootstrap
       classes comes out as one comma-joined class name like
       "choices__inner,sbm-choices-inner" and nothing matches. Arrays are 11.x
       syntax. Styling comes from sidebar-menu-admin.css, scoped to .sbm-modal,
       which needs no help from Bootstrap's dropdown classes. */

    /* Renders each icon as its glyph + its name, in the list and in the field.
       ⚠️ The first argument Choices hands a template is the CONFIG object, not
       `classNames` — `_getTemplate` calls `template.call(this, this.config, …)`.
       Reading `classNames.item` off it yields undefined, every row renders with
       an empty class attribute, and the dropdown looks blank because none of
       Choices' own item styling matches. Destructure `config.classNames`. */
    function iconTemplates(template) {
        function row(config, data, extraAttrs) {
            var classNames = config.classNames;
            var value = escapeHtml(data.value);

            return template(
                '<div class="' + classString(classNames.item) + ' ' + extraAttrs.cls + '" ' +
                'role="option" ' + extraAttrs.attrs +
                ' data-id="' + data.id + '" data-value="' + value + '">' +
                '<span class="sbm-icon-option">' +
                '<i class="material-icons material-symbols-rounded sbm-icon-glyph">' + value + '</i>' +
                '<span class="sbm-icon-label">' + escapeHtml(data.label) + '</span>' +
                '</span></div>'
            );
        }

        return {
            item: function (config, data) {
                return row(config, data, {
                    cls: classString(config.classNames.itemSelectable),
                    attrs: 'data-item ' + (data.active ? 'aria-selected="true"' : '')
                });
            },
            choice: function (config, data) {
                return row(config, data, {
                    cls: classString(config.classNames.itemChoice) + ' ' +
                        classString(config.classNames.itemSelectable),
                    attrs: 'data-select-text="" data-choice ' +
                        (data.disabled ? 'data-choice-disabled aria-disabled="true"' : 'data-choice-selectable')
                });
            }
        };
    }

    /* The whole catalogue as Choices' own data. No `selected` flags here and no
       placeholder entry — the <select> already carries the placeholder <option>,
       which Choices appends to this list, and the current value is applied by
       applyChoiceValue() after construction. An icon that is no longer in the
       catalogue is kept as a "(custom)" entry rather than silently dropped. */
    function iconChoiceList(selected) {
        var list = [];
        var found = false;

        for (var i = 0; i < ICON_NAMES.length; i++) {
            if (ICON_NAMES[i] === selected) { found = true; }
            list.push({ value: ICON_NAMES[i], label: ICON_NAMES[i] });
        }

        if (selected && !found) {
            list.push({ value: selected, label: selected + ' (custom)' });
        }

        return list;
    }

    /* ⚠️ Apply the value AFTER constructing, through Choices' own API.
       Neither `el.value = x` before `new Choices(...)` nor a `selected: true`
       flag in the `choices` array makes the widget RENDER the selection: the
       native <select> ends up right while the box still reads "Select …", which
       is what made Edit look like it had lost the Category and Icon. Only
       setChoiceByValue() updates the rendered item and writes back to the
       <select>. Related trap in docs/new-design-index-page.md §3c. */
    function applyChoiceValue(el, value) {
        if (!el || !el._sbmChoices || !value) {
            // Add mode. The instance is rebuilt from scratch on every open, so it
            // is already showing its placeholder — clearing it here would blank
            // the box instead ("Select Category" would render as empty).
            return;
        }

        try {
            el._sbmChoices.setChoiceByValue(String(value));
        } catch (e) { /* value no longer in the list — leave the placeholder */ }
    }

    function syncIconPreview() {
        var name = $('#icon').val();
        var $i = $('#iconPreview');
        $i.attr('class', 'material-icons material-symbols-rounded');
        $i.toggleClass('is-empty', !name);
        $i.text(name || 'apps');
    }

    function initModalSelects(data) {
        var catEl = document.getElementById('category_id');
        var iconEl = document.getElementById('icon');

        // No Choices on the page (CDN blocked): fall back to native selects, and
        // fill the icon one so it is still usable.
        if (typeof window.Choices === 'undefined') {
            $('#category_id, #icon').addClass('form-select sbm-control');
            if (iconEl && iconEl.options.length <= 1) {
                var frag = document.createDocumentFragment();
                ICON_NAMES.forEach(function (n) { frag.appendChild(new Option(n, n)); });
                iconEl.appendChild(frag);
            }
            if (data) { $('#category_id').val(data.category); $('#icon').val(data.icon); }
            syncIconPreview();
            return;
        }

        $('#category_id, #icon').removeClass('form-select sbm-control');
        destroyChoices(catEl);
        destroyChoices(iconEl);

        if (catEl) {
            catEl._sbmChoices = new Choices(catEl, {
                removeItemButton: false,
                shouldSort: false,
                searchEnabled: true,
                searchPlaceholderValue: 'Search categories…',
                placeholder: true,
                placeholderValue: 'Select Category',
                itemSelectText: '',
                shouldFlip: true
            });
        }

        if (iconEl) {
            iconEl._sbmChoices = new Choices(iconEl, {
                removeItemButton: false,
                shouldSort: false,
                searchEnabled: true,
                searchPlaceholderValue: 'Search icons…',
                placeholder: true,
                itemSelectText: '',
                allowHTML: true,
                shouldFlip: true,
                // ~4,200 icons: render a window of them, not all of them, or the
                // dropdown pins the main thread every time it opens. Search
                // narrows the list, so the cap is never a dead end.
                renderChoiceLimit: 100,
                searchResultLimit: 100,
                choices: iconChoiceList(data ? data.icon : ''),
                callbackOnCreateTemplates: iconTemplates
            });
        }

        // Prefill, THEN bind — setChoiceByValue fires `change`, and binding first
        // would run validation against a form the user has not touched yet.
        applyChoiceValue(catEl, data ? data.category : '');
        applyChoiceValue(iconEl, data ? data.icon : '');

        $(catEl).off('change.sbm').on('change.sbm', function () { $(this).valid(); });
        $(iconEl).off('change.sbm').on('change.sbm', function () {
            syncIconPreview();
            $(this).valid();
        });

        syncIconPreview();
    }

    function destroyModalSelects() {
        destroyChoices(document.getElementById('category_id'));
        destroyChoices(document.getElementById('icon'));
        $('#category_id, #icon').off('change.sbm');
    }

    /* ── Add / Edit modal ────────────────────────────────────────────────────
       One modal, two modes. Everything that differs between them is set here:
       the title, the submit caption, the form action and the _method spoof.
       The selects are built in `shown.bs.modal` from `pendingData`. ── */
    var pendingData = null;

    function openMenuGroupModal(data) {
        var $form = $('#menuGroupForm');

        $form.find('input[name="_method"]').remove();
        if ($form.data('validator')) { $form.validate().resetForm(); }
        $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $form.find('.invalid-feedback').remove();
        $('.choices').removeClass('is-invalid');
        $('#SubmitMenuGroupForm').prop('disabled', false).removeAttr('aria-busy');

        $form[0].reset();
        pendingData = data;

        if (data) {
            $('#MenuGroupModalLabel').text('Edit Menu Group');
            $('#MenuGroupModalSub').text('Update “' + (data.name || 'this menu group') + '”.');
            $('#SubmitMenuGroupForm').text('Update Menu Group');
            $('#name').val(data.name);
            $('#order').val(data.order);
            $('#is_active').val(String(data.status) === '1' ? '1' : '0');
            $form.attr('action', UPDATE_BASE + '/' + data.id)
                 .append('<input type="hidden" name="_method" value="PATCH">');
        } else {
            $('#MenuGroupModalLabel').text('Add Menu Group');
            $('#MenuGroupModalSub').text('Create a new group inside a category.');
            $('#SubmitMenuGroupForm').text('Add Menu Group');
            $form.attr('action', STORE_URL);
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('MenuGroupModal')).show();
    }

    $('#MenuGroupModal')
        .on('shown.bs.modal', function () { initModalSelects(pendingData); })
        .on('hidden.bs.modal', function () { destroyModalSelects(); pendingData = null; });

    $('#sbmAddBtn').on('click', function () { openMenuGroupModal(null); });

    $(document).on('click', '.sbm-edit-btn', function () {
        var $btn = $(this);
        openMenuGroupModal({
            id: $btn.attr('data-id'),
            name: $btn.attr('data-name'),
            category: $btn.attr('data-category'),
            icon: $btn.attr('data-icon'),
            order: $btn.attr('data-order'),
            status: $btn.attr('data-status')
        });
    });

    /* ── Validation ─────────────────────────────────────────────────────────
       `novalidate` on the form leaves enforcement to jquery-validate, which can
       show a message next to the field instead of a native bubble. Choices hides
       the native <select>, so those two are exempted from the `:hidden` ignore
       rule or they would never be validated at all. ── */
    $.validator.addMethod('nameRegex', function (value, element) {
        return this.optional(element) || /^[A-Za-z0-9 .'&()\/-]+$/.test(value);
    }, "Name can only contain letters, numbers, spaces and . ' & ( ) / -");

    $('#menuGroupForm').validate({
        ignore: '.ignore, :hidden:not(.sbm-choices)',
        rules: {
            category_id: { required: true },
            name: { required: true, minlength: 2, maxlength: 100, nameRegex: true },
            icon: { required: true, maxlength: 100 },
            order: { required: false, digits: true },
            is_active: { required: true }
        },
        messages: {
            category_id: { required: 'Please select category' },
            name: {
                required: 'Please enter menu group name',
                minlength: 'Name must be at least 2 characters',
                maxlength: 'Name must be less than 100 characters'
            },
            icon: {
                required: 'Please select an icon',
                maxlength: 'Icon must be less than 100 characters'
            },
            order: { digits: 'Order must be a number' },
            is_active: { required: 'Please select status' }
        },
        errorClass: 'is-invalid',
        validClass: 'is-valid',
        errorElement: 'div',
        highlight: function (element) {
            $(element).addClass('is-invalid').removeClass('is-valid')
                .closest('.choices').addClass('is-invalid');
        },
        unhighlight: function (element) {
            $(element).removeClass('is-invalid').addClass('is-valid')
                .closest('.choices').removeClass('is-invalid');
        },
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            var $wrap = element.closest('.choices');
            if ($wrap.length) { error.insertAfter($wrap); }
            else { element.closest('.form-group').append(error); }
        },
        submitHandler: function (form) {
            var $btn = $('#SubmitMenuGroupForm');
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
