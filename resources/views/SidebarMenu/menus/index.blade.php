@extends('admin.layouts.master')

{{-- Named exactly as the menus row this page hangs off, because the
     breadcrumb component appends the page title as its own crumb whenever
     the two disagree — which read "… / Menus / Sidebar Menus". --}}
@section('title', 'Menus')

@push('styles')
@include('admin.layouts.partials.select2-assets')
{{-- Shared Sidebar Menu Builder chrome — the same module stylesheet the Sidebar
     Categories and Menu Groups grids use, so the three screens cannot drift
     apart. See docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/sidebar-menu-admin.css') }}?v={{ @filemtime(public_path('css/sidebar-menu-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
<div class="container-fluid sbm-page">
    <x-breadcrum title="Menus" :showBack="false">
        <button type="button"
                class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                id="sbmAddBtn">
            <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
            <span>Add Menu</span>
        </button>
    </x-breadcrum>

    <x-session_message />

    {{-- Exports — ABOVE the card, per §1. Nothing here filters by status, so the
         row keeps its place with the buttons alone on the right. --}}
    <div class="d-flex flex-wrap align-items-end justify-content-end gap-3 mb-3">
        <div class="d-flex flex-wrap align-items-end gap-2 sbm-secondary-actions">
            {{-- ?category_id / ?group_id / ?q / ?cols are stamped on by
                 sbmUpdateExportLinks(), so a download carries the same filters,
                 search and columns as the grid. --}}
            <div class="dropdown">
                <button type="button"
                        class="btn programme-dt-btn-columns border-0 text-primary dropdown-toggle"
                        id="sbmDownloadMenuBtn" data-bs-toggle="dropdown" aria-expanded="false"
                        title="Download this list">
                    <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end programme-dt-download-menu"
                    aria-labelledby="sbmDownloadMenuBtn">
                    <li>
                        <a class="dropdown-item" id="sbmCsvLink"
                           href="{{ route('sidebar.menus.export', ['format' => 'csv']) }}">
                            <i class="bi bi-filetype-csv" aria-hidden="true"></i><span>CSV</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" id="sbmExcelLink"
                           href="{{ route('sidebar.menus.export', ['format' => 'excel']) }}">
                            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i><span>Excel</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" id="sbmPdfLink"
                           href="{{ route('sidebar.menus.export', ['format' => 'pdf']) }}">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i><span>PDF</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Print stays OUTSIDE the dropdown: it opens a sheet rather than
                 saving a file, so it is not one of the download formats. --}}
            <a href="{{ route('sidebar.menus.export', ['format' => 'print']) }}"
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
                        <select id="sbmCategoryFilter" class="form-select select2"
                                data-placeholder="Category" aria-label="Filter by category">
                            <option value="">Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Narrowed by the category above; data-category on each option
                         is what sbmSyncGroupFilter() filters on. --}}
                    <div class="programme-dt-filter-select">
                        <select id="sbmGroupFilter" class="form-select select2"
                                data-placeholder="Group" aria-label="Filter by group">
                            <option value="">Group</option>
                            {{-- ->label, not ->name: five group names are used by more
                                 than one group, so the raw name rendered identical rows
                                 (MenuService::disambiguateLabels()). --}}
                            @foreach ($groups as $group)
                                <option value="{{ $group->id }}" data-category="{{ $group->category_id }}">
                                    {{ $group->label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Narrowed by the two above; data-category / data-group on each
                         option is what sbmSyncParentFilter() filters on. Only menus
                         that actually have children are offered, plus the top-level
                         sentinel — an option that could return no rows is not a
                         filter, it is a dead end. --}}
                    <div class="programme-dt-filter-select">
                        <select id="sbmParentFilter" class="form-select select2"
                                data-placeholder="Parent Menu" aria-label="Filter by parent menu">
                            <option value="">Parent Menu</option>
                            <option value="0">— Top level only —</option>
                            @foreach ($parents as $parent)
                                <option value="{{ $parent->id }}"
                                        data-category="{{ $parent->category_id }}"
                                        data-group="{{ $parent->group_id }}">
                                    {{ $parent->label }}
                                </option>
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
                    <div id="sbmDtSearch" class="programme-dt-search" data-dt-search-for="sidebarMenusTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Server-side: search, sort and paging are all SQL, so only the
                         page on screen ever reaches the browser. No `dom`/colVis
                         options here — the global script owns that chrome.
                         13 columns is a lot: the Columns modal is how a user trims
                         it, and .table-responsive scrolls the rest. --}}
                    <table id="sidebarMenusTable"
                           class="table table-hover align-middle mb-0 w-100 programme-dt-table sbm-table-wide">
                        <thead>
                            <tr>
                                <th scope="col">Sr No.</th>
                                <th scope="col">Category</th>
                                <th scope="col">Group</th>
                                <th scope="col">Parent Menu</th>
                                <th scope="col">Name</th>
                                <th scope="col" style="max-width: 200px; text-overflow: ellipsis; white-space: nowrap; overflow: hidden;">Url</th>
                                <th scope="col">Attachment</th>
                                <th scope="col">Permission</th>
                                <th scope="col">Icon</th>
                                <th scope="col">Order</th>
                                <th scope="col">Tab</th>
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
                     data-dt-footer-for="sidebarMenusTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit — one modal, two modes. Same header, field card, labels and
     footer pair either way; only the title and the submit caption change (§3c). --}}
<div class="modal fade" id="MenuModal" tabindex="-1" aria-labelledby="MenuModalLabel"
     data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content sbm-modal border-0 shadow">
            {{-- enctype: the Attachment field below posts a file, and without this
                 the browser sends only its name and the upload silently vanishes. --}}
            <form id="menuForm" action="{{ route('sidebar.menus.store') }}" method="POST"
                  enctype="multipart/form-data" novalidate>
                @csrf
                <input type="hidden" name="id" id="menuId">

                <div class="modal-header sbm-modal-header">
                    <div>
                        <h5 class="modal-title" id="MenuModalLabel">Add Menu</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body sbm-modal-body">
                    {{-- Two columns: the grid gap owns the spacing, so the cells carry
                         no margin of their own (.sbm-form-grid > .form-group). --}}
                    <div class="sbm-field-card sbm-form-grid">
                        <div class="form-group">
                            <label class="sbm-form-label" for="category_id">Category<span class="sbm-req">*</span></label>
                            <select class="form-select sbm-control select2" name="category_id" id="category_id"
                                    data-placeholder="Select Category">
                                <option value="">Select Category</option>
                                @forelse ($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @empty
                                    <option value="" disabled>No category found</option>
                                @endforelse
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="group_id">Group<span class="sbm-req">*</span></label>
                            <select class="form-select sbm-control sidebar-group-select select2"
                                    name="group_id" id="group_id" data-placeholder="Select Group">
                                <option value="">Select Group</option>
                            </select>
                        </div>

                        <div class="form-group sbm-form-grid--full">
                            <label class="sbm-form-label" for="parent_id">Parent Menu</label>
                            <select class="form-select sbm-control sidebar-menu-select select2"
                                    name="parent_id" id="parent_id" data-placeholder="Select Parent Menu">
                                <option value="">Select Parent Menu</option>
                            </select>
                            <p class="sbm-form-help">
                                Pick one to make this a sub-menu of it. Leave blank for a top-level menu.
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="name">Name<span class="sbm-req">*</span></label>
                            <input type="text" class="form-control sbm-control" name="name" id="name"
                                   placeholder="e.g. Course Master" value="{{ old('name') }}"
                                   autocomplete="off" maxlength="100">
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="route">Url</label>
                            <input type="text" class="form-control sbm-control font-monospace" name="route" id="route"
                                   placeholder="e.g. admin/course-master" value="{{ old('route') }}"
                                   autocomplete="off" maxlength="255">
                        </div>

                        {{-- Attachment: same types and 10 MB ceiling as Useful Links,
                             so the two upload fields behave identically.
                             ⚠️ Stored only — the sidebar's links are hand-written in
                             resources/views/components/menu/*.blade.php and do not
                             read this column, so an attachment is reachable from the
                             grid (and the exports), not yet from the menu itself. --}}
                        <div class="form-group sbm-form-grid--full">
                            <label class="sbm-form-label" for="attachment">Attachment</label>
                            <input type="file" class="form-control sbm-control" name="attachment" id="attachment"
                                   accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx,.ppt,.pptx">
                            <p class="sbm-form-help">PDF, image, DOC, XLS or PPT — up to 10 MB. Optional.</p>

                            {{-- Shown only in Edit mode, and only when a file exists;
                                 openEditModal() / openAddModal() drive both. --}}
                            <div id="attachmentCurrentWrap" class="sbm-attachment-current d-none">
                                <a href="#" id="attachmentCurrentLink" class="sbm-attachment"
                                   target="_blank" rel="noopener">
                                    <i class="bi bi-paperclip" aria-hidden="true"></i>
                                    <span id="attachmentCurrentName"></span>
                                </a>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox"
                                           name="remove_attachment" id="removeAttachment" value="1">
                                    <label class="form-check-label" for="removeAttachment">
                                        Remove the current file
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group sbm-form-grid--full">
                            <label class="sbm-form-label" for="permission_name">Permission Name</label>
                            <input type="text" class="form-control sbm-control font-monospace"
                                   name="permission_name" id="permission_name"
                                   placeholder="e.g. course_master" value="{{ old('permission_name') }}"
                                   autocomplete="off" maxlength="255">
                            <p class="sbm-form-help">
                                Filled in from the name until you edit it. A menu with no permission is
                                hidden from every non-admin user.
                            </p>
                        </div>

                        {{-- Icon: text input + a searchable grid panel below it. The
                             ~4,200 names come from the cached static asset
                             admin_assets/js/material-symbols-list.js, not the page. --}}
                        <div class="form-group position-relative">
                            <label class="sbm-form-label" for="icon">Icon</label>
                            <div class="input-group sbm-icon-input-group">
                                <span class="input-group-text sbm-icon-preview-addon">
                                    <i class="material-icons material-symbols-rounded" id="iconPreview"
                                       aria-hidden="true">label</i>
                                </span>
                                <input type="text" class="form-control sbm-control" name="icon" id="icon"
                                       placeholder="Click to search &amp; pick an icon" value="{{ old('icon') }}"
                                       autocomplete="off" maxlength="100">
                            </div>
                            <div class="icon-picker-panel d-none" id="iconPickerPanel">
                                <div class="icon-picker-grid" id="iconPickerGrid"></div>
                                <div class="small text-muted mt-2" id="iconPickerMeta"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="order">Display order</label>
                            <input type="number" class="form-control sbm-control" name="order" id="order"
                                   placeholder="0" value="{{ old('order') }}" inputmode="numeric" min="0">
                            <p class="sbm-form-help">Lower numbers appear first (optional).</p>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="is_active">Status<span class="sbm-req">*</span></label>
                            <select class="form-select sbm-control select2" name="is_active" id="is_active"
                                    data-placeholder="Status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="sbm-form-label" for="target">Opens in</label>
                            <select class="form-select sbm-control select2" name="target" id="target"
                                    data-placeholder="Opens in">
                                <option value="0" selected>Same tab</option>
                                <option value="1">New tab</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer sbm-modal-footer">
                    <button type="button" class="btn sbm-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn sbm-btn-submit" id="SubmitMenuForm">Add Menu</button>
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
                <div class="row g-3" id="sidebarMenuColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('admin_assets/js/material-symbols-list.js') }}"></script>
<script>
$(function () {
    'use strict';

    var STORE_URL = "{{ route('sidebar.menus.store') }}";
    var UPDATE_BASE = "{{ url('sidebar/menus') }}";
    var GROUPS_URL = "{{ route('sidebar.getGroups', ':category_id') }}";
    var MENUS_URL = "{{ route('sidebar.getMenus', ':group_id') }}";

    /* ── DataTable (server-side) ─────────────────────────────────────────────
       `sargamServerOrder` keeps ordering on the server, so clicking a header
       re-sorts the WHOLE set rather than shuffling the visible page. The default
       sort is the Display order column (index 8) — the service's query is left
       unordered on purpose so a header click isn't overruled by it.

       Category and Parent Menu carry no sort/search key:
         · Category is computed (the menu's own category, else its group's), so
           there is no single column to ORDER BY.
         · Parent is a self-join on `menus`; Yajra would join the table to itself
           without an alias and the query breaks.
       Both are still reachable — Category through the filter above, Parent
       through the group filter. ── */
    var dt = $('#sidebarMenusTable').DataTable({
        serverSide: true,
        processing: true,
        sargamServerOrder: true,
        searching: true,
        searchDelay: 400,          // search as you type, one query per pause
        /* Display order — the sidebar's own sequence. ⚠️ This index MUST track the
           columns array below: inserting Attachment at 6 pushed Display order from
           8 to 9, and until this was updated the grid was silently sorting by Icon. */
        order: [[9, 'asc']],
        /* ⚠️ MUST be false. footer.blade.php:80 turns the Responsive extension on
           globally (`$.fn.dataTable.defaults.responsive = true`), and Responsive
           deals with a table wider than its box by hiding the overflow columns
           outright — with 13 columns that silently took the Action column away,
           so rows had no Edit/Delete at all. The design's own
           .programme-dt-panel > .table-responsive scrolls horizontally instead,
           which is the pattern in docs/new-design-index-page.md §3. */
        responsive: false,
        ajax: {
            url: "{{ route('sidebar.menus.index') }}",
            data: function (d) {
                d.category_id = $('#sbmCategoryFilter').val() || '';
                d.group_id = $('#sbmGroupFilter').val() || '';
                d.parent_id = $('#sbmParentFilter').val() || '';
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'category_name', name: 'category_name', orderable: false, searchable: false },
            { data: 'group_name', name: 'group.name' },
            { data: 'parent_menu', name: 'parent_menu', orderable: false, searchable: false },
            { data: 'name', name: 'name' },
            { data: 'route', name: 'route' },
            { data: 'attachment', name: 'attachment' },
            { data: 'permission_name', name: 'permission_name' },
            { data: 'icon', name: 'icon', className: 'text-center' },
            { data: 'order', name: 'order', className: 'text-center' },
            { data: 'target', name: 'target', className: 'text-center' },
            { data: 'created_at', name: 'created_at', className: 'text-nowrap' },
            { data: 'status', name: 'is_active', orderable: false, searchable: false, className: 'text-center' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        language: {
            emptyTable: '<div class="sbm-empty">' +
                '<i class="bi bi-folder-x d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Menus Found</h6>' +
                '<p class="mb-0 small">Get started by adding your first menu.</p>' +
                '</div>',
            zeroRecords: '<div class="sbm-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Menus Found</h6>' +
                '<p class="mb-0 small">No menu matches your filters.</p>' +
                '</div>'
        }
    });

    /* ── Filters ─────────────────────────────────────────────────────────────
       The Group list is narrowed by the chosen Category. Options are hidden by
       DETACHING them rather than by CSS: `display:none` on an <option> is
       ignored by several browsers, which would leave the list looking unfiltered. */
    var $groupFilter = $('#sbmGroupFilter');
    var groupFilterOptions = $groupFilter.find('option[data-category]').clone();

    function sbmSyncGroupFilter() {
        var categoryId = $('#sbmCategoryFilter').val() || '';
        var current = $groupFilter.val();

        $groupFilter.find('option[data-category]').remove();
        groupFilterOptions.each(function () {
            if (!categoryId || String($(this).data('category')) === String(categoryId)) {
                $groupFilter.append($(this).clone());
            }
        });

        // Keep the group only if it still belongs to the chosen category.
        $groupFilter.val($groupFilter.find('option[value="' + current + '"]').length ? current : '')
            .trigger('change.select2');
    }

    /* Parent Menu is narrowed by BOTH selects above it. Same detach-don't-hide
       rule as the group list. The "0" (top level only) option is never filtered
       out — it is meaningful under any category/group. */
    var $parentFilter = $('#sbmParentFilter');
    var parentFilterOptions = $parentFilter.find('option[data-group]').clone();

    function sbmSyncParentFilter() {
        var categoryId = $('#sbmCategoryFilter').val() || '';
        var groupId = $groupFilter.val() || '';
        var current = $parentFilter.val();

        $parentFilter.find('option[data-group]').remove();
        parentFilterOptions.each(function () {
            var $opt = $(this);
            if (groupId && String($opt.data('group')) !== String(groupId)) { return; }
            // A menu inherits its category from its group when it has none of its
            // own, so an empty data-category must not exclude it here.
            var optCategory = $opt.attr('data-category');
            if (categoryId && optCategory && String(optCategory) !== String(categoryId)) { return; }
            $parentFilter.append($opt.clone());
        });

        // Keep the parent only if it survived the narrowing.
        $parentFilter.val($parentFilter.find('option[value="' + current + '"]').length ? current : '')
            .trigger('change.select2');
    }

    $('#sbmCategoryFilter').on('change', function () {
        sbmSyncGroupFilter();
        sbmSyncParentFilter();
        dt.ajax.reload();
        sbmUpdateExportLinks();
    });

    $groupFilter.on('change', function () {
        sbmSyncParentFilter();
        dt.ajax.reload();
        sbmUpdateExportLinks();
    });

    $parentFilter.on('change', function () {
        dt.ajax.reload();
        sbmUpdateExportLinks();
    });

    $('#sbmResetFilters').on('click', function () {
        // Clear the GROUP explicitly. sbmSyncGroupFilter() only re-derives the
        // option list; with no category chosen every group is back in it, so the
        // current selection survives and Reset silently left the grid filtered.
        // .val('') alone leaves Select2 still SHOWING the cleared value —
        // the widget only repaints on its own namespaced event.
        $('#sbmCategoryFilter').val('').trigger('change.select2');
        $groupFilter.val('').trigger('change.select2');
        $parentFilter.val('').trigger('change.select2');
        sbmSyncGroupFilter();
        sbmSyncParentFilter();
        dt.search('').ajax.reload();      // clears the search box too
        sbmUpdateExportLinks();
    });

    /* ── Column visibility (DataTables column API) ────────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one.
       An unknown label is simply ignored, so a renamed column comes back
       visible — the safe direction to fail in. ── */
    var COL_KEY = 'sidebarMenuGrid:hiddenColumns:v1';

    /* Header index -> the export key the server understands
       (MenuService::exportColumnDefs()). Positional: '' marks a column that is
       not in the export at all — here, Action.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var SBM_EXPORT_COLUMN_KEYS = [
        'sno', 'category', 'group', 'parent', 'name', 'route', 'attachment',
        'permission_name', 'icon', 'order', 'target', 'created_at', 'status', ''
    ];
    var SBM_EXPORT_COL_COUNT = SBM_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep Download and Print carrying exactly the columns still on screen, plus
       the filters and search term currently applied to the grid. */
    function sbmUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = SBM_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var term = dt.search() || '';
        var category = $('#sbmCategoryFilter').val() || '';
        var group = $groupFilter.val() || '';
        var parent = $parentFilter.val() || '';

        ['sbmCsvLink', 'sbmExcelLink', 'sbmPdfLink', 'sbmPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('category_id');
            if (category !== '') { params.set('category_id', category); }

            params.delete('group_id');
            if (group !== '') { params.set('group_id', group); }

            // '0' is the top-level-only sentinel, so compare against '' not falsy.
            params.delete('parent_id');
            if (parent !== '') { params.set('parent_id', parent); }

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
        var $grid = $('#sidebarMenuColumnToggleGrid');
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

            var inputId = 'sbmmcolvis_' + index;
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

    /* Just created a menu? Open the grid on it. With 240+ menus over 25 pages a
       create that drops the user on page 1 looks exactly like one that silently
       failed. DataTables' own search box is relocated into #sbmDtSearch by
       datatable-global-ui.js, so drive the search through the API and mirror it
       into the visible input.

       ⚠️ Must sit AFTER sbmUpdateExportLinks() is defined AND first called:
       SBM_EXPORT_COLUMN_KEYS is a `var` declared above it, so calling it any
       earlier reads an undefined array. */
    @if (session('created_menu'))
        (function () {
            var justCreated = @json(session('created_menu'));
            $('#sbmDtSearch input').val(justCreated);
            dt.search(justCreated).draw();
            sbmUpdateExportLinks();
        })();
    @endif

    /* ── Status switch ───────────────────────────────────────────────────────
       This grid has its own endpoint rather than the generic table/column one
       custom.js binds `.status-toggle` to, so the confirm + AJAX live here. The
       badge and the switch are in different columns, so redraw from the server
       on success rather than hand-mirroring them. ── */
    $(document).on('change', '.sidebar-menu-status-toggle', function () {
        var $checkbox = $(this);
        var id = $checkbox.data('id');
        var value = $checkbox.is(':checked') ? 1 : 0;
        var actionText = value === 1 ? 'activate' : 'deactivate';
        var name = $checkbox.data('name') || 'this menu';

        function revert() { $checkbox.prop('checked', value !== 1); }

        function send() {
            $.ajax({
                url: "{{ route('sidebar.menus.status', ':id') }}".replace(':id', id),
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

        var name = $(form).find('.sbm-act--del').data('name') || 'this menu';

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

    /* ══ Dependent dropdowns: Category → Group → Parent Menu ═══════════════ */

    function resetParentMenuSelect() {
        $('#parent_id').empty().append('<option value="">Select Parent Menu</option>')
            .trigger('change.select2');
    }

    function resetGroupSelect() {
        $('#group_id').empty().append('<option value="">Select Group</option>')
            .trigger('change.select2');
    }

    /**
     * @param {string|number} categoryId
     * @param {string} selectedGroupId  group to re-select once loaded ('' for none)
     * @param {Function} [done]         called after the list is in place
     */
    function loadGroups(categoryId, selectedGroupId, done) {
        resetGroupSelect();

        if (!categoryId) {
            if (typeof done === 'function') { done(); }
            return;
        }

        $.ajax({
            url: GROUPS_URL.replace(':category_id', categoryId),
            type: 'GET',
            data: { _token: "{{ csrf_token() }}" },
            success: function (response) {
                if (response && response.success && response.groups) {
                    response.groups.forEach(function (group) {
                        $('#group_id').append(
                            $('<option></option>').attr('value', group.id).text(group.name)
                        );
                    });
                }
                // Assign AFTER the options exist — setting it first is silently
                // dropped (docs/new-design-index-page.md §3c).
                $('#group_id').val(selectedGroupId || '');
                // Select2 reads the <option>s live but only repaints the closed
                // box on its own namespaced event (§3c).
                $('#group_id').trigger('change.select2');
                if (typeof done === 'function') { done(); }
            },
            error: function () {
                if (typeof toastr !== 'undefined') { toastr.error('Could not load groups for this category'); }
                if (typeof done === 'function') { done(); }
            }
        });
    }

    /**
     * @param {string|number} groupId
     * @param {string|number} excludeMenuId  the row being edited — a menu cannot
     *                                       be its own parent
     * @param {string} selectedParentId
     */
    function loadParentMenus(groupId, excludeMenuId, selectedParentId, done) {
        resetParentMenuSelect();

        if (!groupId) {
            if (typeof done === 'function') { done(); }
            return;
        }

        var url = MENUS_URL.replace(':group_id', groupId);
        if (excludeMenuId) { url += '?exclude_id=' + encodeURIComponent(excludeMenuId); }

        $.ajax({
            url: url,
            type: 'GET',
            data: { _token: "{{ csrf_token() }}" },
            success: function (response) {
                if (response && response.success && response.menus) {
                    response.menus.forEach(function (menu) {
                        $('#parent_id').append(
                            $('<option></option>').attr('value', menu.id).text(menu.name)
                        );
                    });
                }
                $('#parent_id').val(selectedParentId || '');
                $('#parent_id').trigger('change.select2');
                if (typeof done === 'function') { done(); }
            },
            error: function () {
                if (typeof toastr !== 'undefined') { toastr.error('Could not load parent menus'); }
                if (typeof done === 'function') { done(); }
            }
        });
    }

    // Changing Category invalidates both Group and Parent; changing Group
    // invalidates Parent. Rebuild downwards, never sideways.
    $('#category_id').on('change', function () {
        loadGroups(this.value, '', function () { resetParentMenuSelect(); });
    });

    $('#group_id').on('change', function () {
        loadParentMenus(this.value, $('#menuId').val() || null, '', null);
    });

    /* ── Permission name mirrors the menu name until the user edits it ────── */
    var permissionTouched = false;

    $('#permission_name').on('keyup', function () { permissionTouched = true; });

    $('#name').on('keyup', function () {
        if (permissionTouched) { return; }
        $('#permission_name').val(
            $(this).val().toLowerCase().trim()
                .replace(/-/g, '_')
                .replace(/[^a-z0-9_\s]/g, '')
                .replace(/\s+/g, '_')
        );
    });

    /* ── Add / Edit modal ────────────────────────────────────────────────────
       One modal, two modes. Edit opens only AFTER its dependent dropdowns have
       been populated, so the user never sees the Group/Parent boxes fill in
       under them. ── */
    function showMenuModal() {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('MenuModal')).show();
    }

    function resetMenuForm() {
        var $form = $('#menuForm');
        $form.find('input[name="_method"]').remove();
        if ($form.data('validator')) { $form.validate().resetForm(); }
        $form.find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
        $form.find('.invalid-feedback').remove();
        $('#SubmitMenuForm').prop('disabled', false).removeAttr('aria-busy');
        $form[0].reset();
        // .reset() restores the native <select>s but Select2 keeps painting the
        // last values; repaint every one of them or Add opens pre-filled with
        // whatever the previous Edit left behind.
        $form.find('select.select2').trigger('change.select2');
        return $form;
    }

    function openAddModal() {
        var $form = resetMenuForm();
        permissionTouched = false;

        $('#MenuModalLabel').text('Add Menu');
        $('#MenuModalSub').text('Create a new menu inside a group.');
        $('#SubmitMenuForm').text('Add Menu');
        $('#menuId').val('');
        $form.attr('action', STORE_URL);

        resetGroupSelect();
        resetParentMenuSelect();
        syncIconPreview();
        sbmShowCurrentAttachment(null);
        $('#is_container').prop('checked', false);
        sbmSyncContainerFields();
        showMenuModal();
    }

    function openEditModal(data) {
        var $form = resetMenuForm();
        // Edit prefills it, so the name must not overwrite it as the user types.
        permissionTouched = true;

        $('#MenuModalLabel').text('Edit Menu');
        $('#MenuModalSub').text('Update “' + (data.name || 'this menu') + '”.');
        $('#SubmitMenuForm').text('Update Menu');

        $('#menuId').val(data.id);
        $('#name').val(data.name);
        $('#route').val(data.route || '');
        $('#permission_name').val(data.permission_name || '');
        $('#icon').val(data.icon || '');
        $('#order').val(data.order === null || data.order === undefined ? '' : data.order);
        // Select2 keeps the native <select> in sync but only repaints its own
        // box on change.select2 — without this Edit opens showing the
        // placeholder instead of the saved value (§3c).
        $('#is_active').val(String(data.is_active) === '1' ? '1' : '0').trigger('change.select2');
        $('#target').val(String(data.target) === '1' ? '1' : '0').trigger('change.select2');
        $form.attr('action', UPDATE_BASE + '/' + data.id)
             .append('<input type="hidden" name="_method" value="PUT">');

        $('#category_id').val(data.category_id || '').trigger('change.select2');
        syncIconPreview();
        sbmShowCurrentAttachment(data.attachment || null);
        $('#is_container').prop('checked', String(data.is_container) === '1');
        sbmSyncContainerFields();

        // Chain the two AJAX loads, then open — Group must exist before Parent
        // can be filtered by it.
        loadGroups(data.category_id, data.group_id || '', function () {
            loadParentMenus(
                data.group_id,
                data.id,
                data.parent_id ? String(data.parent_id) : '',
                showMenuModal
            );
        });
    }

    /* Current attachment: shown in Edit when the row has one, hidden otherwise.
       The Remove checkbox is always cleared here — resetMenuForm() runs before
       this, but a checkbox left ticked from a previous edit would silently
       delete the next menu's file. */
    var ATTACHMENT_BASE = "{{ asset('storage') }}/";

    function sbmShowCurrentAttachment(path) {
        var $wrap = $('#attachmentCurrentWrap');
        $('#removeAttachment').prop('checked', false);

        if (!path) {
            $wrap.addClass('d-none');
            return;
        }

        $('#attachmentCurrentLink').attr('href', ATTACHMENT_BASE + path);
        $('#attachmentCurrentName').text(String(path).split('/').pop());
        $wrap.removeClass('d-none');
    }

    /* A container has no destination, so Url and Attachment are cleared and
       locked while the box is ticked — the server enforces the same rule, this
       just stops the user filling in a field that is about to be rejected. */
    function sbmSyncContainerFields() {
        var isContainer = $('#is_container').is(':checked');

        $('#route').prop('disabled', isContainer);
        $('#attachment').prop('disabled', isContainer);

        if (isContainer) {
            $('#route').val('');
            $('#attachment').val('');
            $('#removeAttachment').prop('checked', true);
        }

        $('#attachmentCurrentWrap').toggleClass('sbm-dim', isContainer);
    }

    $('#is_container').on('change', sbmSyncContainerFields);

    $('#sbmAddBtn').on('click', openAddModal);

    $(document).on('click', '.sbm-edit-btn', function () {
        var data = $(this).data('item');
        if (typeof data === 'string') {
            try { data = JSON.parse(data); }
            catch (e) {
                if (typeof toastr !== 'undefined') { toastr.error('Could not load menu data for editing'); }
                return;
            }
        }
        openEditModal(data);
    });

    /* ── Validation ─────────────────────────────────────────────────────────
       `novalidate` on the form leaves enforcement to jquery-validate, which can
       show a message next to the field instead of a native bubble. ── */
    $('#menuForm').validate({
        ignore: '.ignore',
        rules: {
            category_id: { required: true },
            group_id: { required: true },
            name: { required: true, minlength: 2, maxlength: 100 },
            route: { required: false, maxlength: 255 },
            permission_name: { required: false, minlength: 2, maxlength: 255 },
            icon: { maxlength: 100 },
            order: { required: false, digits: true },
            is_active: { required: true },
            target: { required: true }
        },
        messages: {
            category_id: { required: 'Please select category' },
            group_id: { required: 'Please select group' },
            name: {
                required: 'Please enter menu name',
                minlength: 'Name must be at least 2 characters',
                maxlength: 'Name must be less than 100 characters'
            },
            route: { maxlength: 'Url must be less than 255 characters' },
            permission_name: {
                minlength: 'Permission name must be at least 2 characters',
                maxlength: 'Permission name must be less than 255 characters'
            },
            icon: { maxlength: 'Icon must be less than 100 characters' },
            order: { digits: 'Order must be a number' },
            is_active: { required: 'Please select status' },
            target: { required: 'Please select where the menu opens' }
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
            var $btn = $('#SubmitMenuForm');
            $btn.prop('disabled', true).attr('aria-busy', 'true').html(
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                '<span>Processing…</span>'
            );
            form.submit();
        }
    });

    /* ══ Material Symbols icon picker ═════════════════════════════════════════
       The ~4,200 names come from admin_assets/js/material-symbols-list.js — a
       static, browser-cached asset, so they cost nothing per page load. Only the
       first 200 matches are rendered; typing narrows them. ── */
    var ICONS = window.MATERIAL_SYMBOLS || [];
    var MAX_RENDER = 200;
    var $iconInput = $('#icon');
    var $iconPreview = $('#iconPreview');
    var $iconPanel = $('#iconPickerPanel');
    var $iconGrid = $('#iconPickerGrid');
    var $iconMeta = $('#iconPickerMeta');

    function syncIconPreview() {
        var value = ($iconInput.val() || '').trim();
        $iconPreview.toggleClass('is-empty', !value).text(value || 'label');
    }

    function renderIconGrid(term) {
        term = (term || '').toLowerCase().trim();
        var matches = term
            ? ICONS.filter(function (n) { return n.indexOf(term) !== -1; })
            : ICONS;
        var shown = matches.slice(0, MAX_RENDER);
        var current = ($iconInput.val() || '').trim();
        var frag = document.createDocumentFragment();

        shown.forEach(function (name) {
            var cell = document.createElement('div');
            cell.className = 'icon-picker-cell' + (name === current ? ' active' : '');
            cell.setAttribute('data-icon', name);
            cell.title = name;
            cell.innerHTML = '<i class="material-icons material-symbols-rounded"></i><small></small>';
            cell.firstChild.textContent = name;
            cell.lastChild.textContent = name;
            frag.appendChild(cell);
        });

        $iconGrid.empty();
        $iconGrid[0].appendChild(frag);

        if (!matches.length) {
            $iconMeta.text(term ? ('No icons match "' + term + '".') : 'No icons available.');
        } else if (matches.length > shown.length) {
            $iconMeta.text('Showing ' + shown.length + ' of ' + matches.length + ' — type to refine.');
        } else {
            $iconMeta.text(matches.length + ' icon' + (matches.length === 1 ? '' : 's') + '.');
        }
    }

    if ($iconInput.length && $iconPanel.length) {
        $iconInput.on('focus click', function () {
            renderIconGrid($iconInput.val());
            $iconPanel.removeClass('d-none');
        });

        $iconInput.on('input', function () {
            syncIconPreview();
            renderIconGrid($iconInput.val());
            $iconPanel.removeClass('d-none');
        });

        $iconGrid.on('click', '.icon-picker-cell', function () {
            $iconInput.val(this.getAttribute('data-icon'));
            syncIconPreview();
            $iconPanel.addClass('d-none');
        });

        // Close when clicking outside the icon field or the panel.
        $(document).on('mousedown', function (e) {
            if (!$(e.target).closest('#iconPickerPanel, #icon').length) {
                $iconPanel.addClass('d-none');
            }
        });

        $('#MenuModal').on('shown.bs.modal', function () {
            syncIconPreview();
            $iconPanel.addClass('d-none');
        });
    }
});
</script>
@endpush
