@extends('admin.layouts.master')

@section('title', 'Assign Permission')

@push('styles')
{{-- Shared Roles & Permissions chrome — the same module stylesheet the Roles
     grid uses, so the two screens cannot drift apart.
     See docs/new-design-index-page.md §3b. --}}
<link rel="stylesheet"
      href="{{ asset('css/roles-permissions-admin.css') }}?v={{ @filemtime(public_path('css/roles-permissions-admin.css')) ?: time() }}">
@endpush

@section('setup_content')
@php
    $totalCount = count($rows);
    $disabledCount = $totalCount - $enabledCount;
@endphp
<div class="container-fluid rp-page">
    <x-breadcrum title="Assign Permission - {{ ucfirst($role->name) }}" :items="['Setup', 'Hr Management', 'Assign Permission']">
    </x-breadcrum>

    <x-session_message />

    {{-- Status pills + exports — ABOVE the card, per §1. The pills are the grid's
         status filter; each one redraws the table client-side. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        @php
            $rpPills = [
                ['value' => '', 'label' => 'All', 'count' => $totalCount],
                ['value' => 'enabled', 'label' => 'Enabled', 'count' => $enabledCount],
                ['value' => 'disabled', 'label' => 'Disabled', 'count' => $disabledCount],
            ];
        @endphp
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter permissions by status">
            @foreach ($rpPills as $pill)
                <li class="nav-item" role="presentation">
                    <button type="button"
                            class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill rp-status-pill{{ $loop->first ? ' active' : '' }}"
                            data-rp-status="{{ $pill['value'] }}"
                            aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                            @if ($loop->first) aria-current="true" @endif>
                        {{ $pill['label'] }}
                        <span class="rp-pill-count">{{ number_format($pill['count']) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2 rp-secondary-actions">
            {{-- ?q / ?category / ?status / ?cols are stamped on by
                 rpUpdateExportLinks(), so a download is the matrix as filtered. --}}
            <a href="{{ route('roles.permissions.export', ['id' => $role->id, 'format' => 'csv']) }}"
               id="rpDownloadLink"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </a>
            <a href="{{ route('roles.permissions.export', ['id' => $role->id, 'format' => 'print']) }}"
               id="rpPrintLink" target="_blank" rel="noopener"
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
                        <select id="rpCategoryFilter" class="form-select" aria-label="Filter by category">
                            <option value="">Category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <button type="button" class="btn programme-dt-btn-reset" id="rpResetFilters">Reset Filters</button>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="rpBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#rpPermColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="rpDtSearch" class="programme-dt-search" data-dt-search-for="permissionTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Client-side: every assignable permission is rendered here
                         (175 rows for the full menu tree) and DataTables paginates
                         them in the browser. There is no server paginator to
                         conflict with — see the trap in §9. --}}
                    <table id="permissionTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">Category</th>
                                <th scope="col">Group</th>
                                <th scope="col">Menu</th>
                                <th scope="col">Sub Menu</th>
                                <th scope="col">Permission</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        {{-- $rows is RoleService::permissionMatrix() — the same flat
                             list the export writes, so the two cannot disagree. --}}
                        @foreach ($rows as $row)
                            <tr>
                                <td>{{ $row['category'] }}</td>
                                <td>{{ $row['group'] }}</td>
                                <td>{{ $row['menu'] }}</td>
                                <td>{{ $row['submenu'] }}</td>
                                <td>
                                    @if (filled($row['permission']))
                                        <span class="rp-slug">{{ $row['permission'] }}</span>
                                    @else
                                        <span class="rp-muted">—</span>
                                    @endif
                                </td>
                                {{-- Status column = display only; data-order lets the
                                     header sort by state (§3b). --}}
                                <td data-order="{{ $row['enabled'] ? 1 : 0 }}" class="text-center">
                                    <span class="status-pill badge rounded-1 {{ $row['enabled'] ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $row['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                {{-- Action column = the control. No .form-check/.form-switch
                                     wrapper: that combination yanks the input -2.375rem
                                     left (custom.css:107-112) and breaks the stack (§3b). --}}
                                <td>
                                    <div class="rp-act-group" role="group" aria-label="Permission for {{ $row['menu'] }}">
                                        <label class="rp-act rp-act--toggle" for="rpPerm{{ $row['id'] }}">
                                            <span class="rp-act__icon">
                                                <input class="form-check-input plain-status-toggle permission-toggle"
                                                       type="checkbox" role="switch"
                                                       id="rpPerm{{ $row['id'] }}"
                                                       name="permissions[]"
                                                       data-id="{{ $row['id'] }}"
                                                       data-label="{{ $row['submenu'] !== '-' ? $row['submenu'] : $row['menu'] }}"
                                                       value="{{ $row['permission'] }}"
                                                       @checked($row['enabled'])>
                                            </span>
                                            <span class="rp-act__label">{{ $row['enabled'] ? 'Revoke' : 'Grant' }}</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Footer variant A — DataTables paginates, datatable-global-ui.js
                     fills this in with the pager and "Showing [10] of N items" (§4). --}}
                <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                     data-dt-footer-for="permissionTable"></div>
            </div>

        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
<div class="modal fade" id="rpPermColumnVisibilityModal" tabindex="-1"
     aria-labelledby="rpPermColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="rpPermColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="rpPermColumnToggleGrid"></div>
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

    /* Status pills own this; the ext.search predicate below reads it. */
    var rpStatus = '';

    /* ── Status filter ───────────────────────────────────────────────────────
       Enabled/Disabled is the live state of the switch, not table text, so it
       cannot be a column search — it needs a predicate. Registered once and
       scoped by table id so it can't affect other grids. ── */
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'permissionTable') { return true; }
        if (!rpStatus) { return true; }
        var checked = $(settings.aoData[dataIndex].nTr).find('.permission-toggle').is(':checked');
        return rpStatus === 'enabled' ? checked : !checked;
    });

    /* ── DataTable (client-side) ─────────────────────────────────────────────
       Every row is already in the DOM, so DataTables both filters and paginates
       in the browser. No `dom` or colVis options here — the global script owns
       that chrome and puts the search box and footer in the slots above. ── */
    var dt = $('#permissionTable').DataTable({
        autoWidth: false,
        order: [],
        /* footer.blade.php:80 turns the Responsive extension on globally. It
           deals with a table wider than its box by HIDING columns (on a narrow
           screen that takes the Action column — the switch — away) and swaps in
           its own +/− child-row chrome, which is not this design's. The panel's
           .table-responsive scrolls horizontally instead — §3. */
        responsive: false,
        columnDefs: [{ orderable: false, targets: 6 }],   // Action (the switch)
        language: {
            emptyTable: '<div class="rp-empty">' +
                '<i class="bi bi-shield-lock d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Permissions Available</h6>' +
                '<p class="mb-0 small">This role has no assignable menus yet.</p>' +
                '</div>',
            zeroRecords: '<div class="rp-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Permissions Found</h6>' +
                '<p class="mb-0 small">No permission matches your filters.</p>' +
                '</div>'
        }
    });

    /* ── Filters ─────────────────────────────────────────────────────────── */
    function applyFilters() {
        var category = $('#rpCategoryFilter').val() || '';
        // Category is an exact match on column 0; the search box covers the rest.
        dt.column(0)
          .search(category ? '^' + $.fn.dataTable.util.escapeRegex(category) + '$' : '', true, false)
          .draw();
        rpUpdateExportLinks();
    }

    $('#rpCategoryFilter').on('change', applyFilters);

    $('.rp-status-pill').on('click', function () {
        var $pill = $(this);
        if ($pill.hasClass('active')) { return; }

        $('.rp-status-pill').removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $pill.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

        rpStatus = $pill.attr('data-rp-status') || '';
        dt.draw();
        rpUpdateExportLinks();
    });

    $('#rpResetFilters').on('click', function () {
        $('#rpCategoryFilter').val('');
        rpStatus = '';
        $('.rp-status-pill').removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $('.rp-status-pill[data-rp-status=""]').addClass('active')
            .attr({ 'aria-pressed': 'true', 'aria-current': 'true' });
        dt.search('');                 // clears the relocated search box too
        applyFilters();
    });

    /* ── Column visibility (DataTables column API) ────────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one.
       An unknown label is simply ignored, so a renamed column comes back
       visible — the safe direction to fail in. ── */
    var COL_KEY = 'rolePermissionGrid:hiddenColumns:v1';

    /* Header index -> the export key the server understands
       (RoleService::permissionExportColumnDefs()). Positional: '' marks a column
       that is not in the export at all — here, Action, whose Status twin already
       carries the state.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var RP_EXPORT_COLUMN_KEYS = ['category', 'group', 'menu', 'submenu', 'permission', 'status', ''];
    var RP_EXPORT_COL_COUNT = RP_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep Download and Print carrying exactly the columns still on screen, plus
       the filters and search term currently applied to the grid. */
    function rpUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = RP_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var term = dt.search() || '';
        var category = $('#rpCategoryFilter').val() || '';

        ['rpDownloadLink', 'rpPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('category');
            if (category !== '') { params.set('category', category); }

            params.delete('status');
            if (rpStatus !== '') { params.set('status', rpStatus); }

            params.delete('cols');
            // Sr No. is generated by the export itself, so it is never in `keys`
            // and must not count towards "everything is visible".
            if (keys.length !== RP_EXPORT_COL_COUNT) { params.set('cols', 'sno,' + keys.join(',')); }

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
        var $grid = $('#rpPermColumnToggleGrid');
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

            var inputId = 'rppermcolvis_' + index;
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

    /* ── Grant / revoke ──────────────────────────────────────────────────────
       The switch is the control; the Status badge one column over is the
       display. On success repaint BOTH from the row itself — the whole table is
       already in the browser, so there is nothing to re-fetch. On failure put
       the switch back where it was. ── */
    function paintRow($checkbox, enabled) {
        var $row = $checkbox.closest('tr');

        $row.find('.status-pill')
            .toggleClass('bg-success-subtle', enabled)
            .toggleClass('bg-danger-subtle', !enabled)
            .text(enabled ? 'Enabled' : 'Disabled');

        // Keep the sort key in step with the badge, or ordering by Status goes
        // stale the moment someone toggles a row.
        $row.find('td[data-order]').attr('data-order', enabled ? 1 : 0);

        // The caption names the ACTION, not the state (§3b).
        $checkbox.closest('.rp-act').find('.rp-act__label').text(enabled ? 'Revoke' : 'Grant');

        rpUpdatePillCounts();
    }

    function rpUpdatePillCounts() {
        /* ⚠️ Count through the DataTables row set, NOT the DOM. A paginated
           client-side table keeps only the current page's <tr>s in the tbody, so
           `$('#permissionTable tbody ...')` sees 10 rows and the pills would
           read "All 10 / Enabled 9" the moment anyone toggled something.
           rows() with no selector spans every row regardless of page or filter. */
        var $toggles = dt.rows().nodes().to$().find('.permission-toggle');
        var total = $toggles.length;
        var enabled = $toggles.filter(':checked').length;

        $('.rp-status-pill[data-rp-status=""] .rp-pill-count').text(total.toLocaleString());
        $('.rp-status-pill[data-rp-status="enabled"] .rp-pill-count').text(enabled.toLocaleString());
        $('.rp-status-pill[data-rp-status="disabled"] .rp-pill-count').text((total - enabled).toLocaleString());
    }

    $(document).on('change', '.permission-toggle', function () {
        var $checkbox = $(this);
        var enabled = $checkbox.is(':checked');

        $.ajax({
            url: "{{ route('assign.roles.permissions', $role->id) }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                menu_id: $checkbox.data('id'),
                permission: $checkbox.val(),
                status: enabled ? 1 : 0
            },
            success: function (response) {
                if (response && response.success) {
                    paintRow($checkbox, enabled);
                    if (typeof toastr !== 'undefined') { toastr.success(response.message); }
                    // Re-apply the status filter: a row that no longer matches the
                    // active pill should leave the view.
                    if (rpStatus) { dt.draw(false); }
                } else {
                    $checkbox.prop('checked', !enabled);
                    if (typeof toastr !== 'undefined') {
                        toastr.error((response && response.message) || 'Something went wrong');
                    }
                }
            },
            error: function () {
                $checkbox.prop('checked', !enabled);
                if (typeof toastr !== 'undefined') { toastr.error('Something went wrong'); }
            }
        });
    });
});
</script>
@endpush
