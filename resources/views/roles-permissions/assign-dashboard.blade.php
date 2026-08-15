@extends('admin.layouts.master')

@section('title', 'Assign Dashboard')

@push('styles')
{{-- Shared Roles & Permissions chrome — the same module stylesheet the Roles
     grid and Assign Permission use, so the three screens cannot drift apart.
     See docs/new-design-index-page.md §3b/§3c. --}}
<link rel="stylesheet"
      href="{{ asset('css/roles-permissions-admin.css') }}?v={{ @filemtime(public_path('css/roles-permissions-admin.css')) ?: time() }}">
{{-- ⚠️ PIN the version. The unpinned URL now serves Choices 11.x, whose template
     callback is handed the CONFIG object where 10.x hands it `classNames` — so
     every rendered choice came out with an empty class attribute and the icon
     dropdown looked completely empty. 10.2.0 is what the other views in this app
     pin, so this keeps one version across the codebase. --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css" />
@endpush

@section('setup_content')
@php
    $cardRows = app(\App\Services\RoleService::class)->dashboardCardRows($allCards, $assignedCardIds);
    $totalCount = count($cardRows);
    $disabledCount = $totalCount - $enabledCount;
@endphp
<div class="container-fluid rp-page">
    <x-breadcrum title="Assign Dashboard - {{ ucfirst($role->name) }}" :items="['Setup', 'Hr Management', 'Assign Dashboard']" :showBack="true" :backUrl="route('roles.index')">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button"
                    class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 rounded-1 fw-semibold shadow-sm"
                    id="rpAddCardBtn">
                <i class="material-icons material-symbols-rounded" style="font-size:18px;" aria-hidden="true">add</i>
                <span>Add New Card</span>
            </button>
        </div>
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
            role="group" aria-label="Filter cards by status">
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
            {{-- ?q / ?status / ?cols are stamped on by rpUpdateExportLinks(), so a
                 download is the card list as filtered. --}}
            <a href="{{ route('roles.dashboard.export', ['id' => $role->id, 'format' => 'csv']) }}"
               id="rpDownloadLink"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Download as CSV">
                <i class="bi bi-download" aria-hidden="true"></i><span>Download</span>
            </a>
            <a href="{{ route('roles.dashboard.export', ['id' => $role->id, 'format' => 'print']) }}"
               id="rpPrintLink" target="_blank" rel="noopener"
               class="btn programme-dt-btn-columns border-0 text-primary" title="Print">
                <i class="bi bi-printer" aria-hidden="true"></i><span>Print</span>
            </a>
        </div>
    </div>

    <div class="card overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            {{-- Toolbar: reset left, columns + search right (§2). Status lives in
                 the pills above, so the only filter left to clear is the search. --}}
            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4
                        programme-dt-toolbar">

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="rpBtnColumns"
                            data-bs-toggle="modal" data-bs-target="#rpCardColumnVisibilityModal"
                            title="Show / hide columns"
                            style="border: 1px solid #d0d5dd; background: #fff; color: #344054;">
                        <span>Columns</span><i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- datatable-global-ui.js moves DataTables' own filter in here. --}}
                    <div id="rpDtSearch" class="programme-dt-search" data-dt-search-for="dashboardCardTable"></div>
                </div>
            </div>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- Client-side: every card is rendered here and DataTables
                         paginates them in the browser. There is no server paginator
                         to conflict with — see the trap in §9. --}}
                    <table id="dashboardCardTable" class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                <th scope="col">S No.</th>
                                <th scope="col">Name</th>
                                <th scope="col">Icon</th>
                                <th scope="col">Colour</th>
                                <th scope="col">Order</th>
                                <th scope="col">Created</th>
                                <th scope="col">Status</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        {{-- $cardRows is RoleService::dashboardCardRows() — the same
                             list the export writes, so the two cannot disagree. --}}
                        @foreach ($cardRows as $index => $row)
                            <tr data-id="{{ $row['id'] }}">
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>{{ $row['label'] }}</td>
                                <td class="text-center">
                                    <span class="rp-card-icon {{ $row['color_class'] }}" title="{{ $row['icon'] }}">
                                        <i class="material-icons material-symbols-rounded" aria-hidden="true">{{ $row['icon'] }}</i>
                                    </span>
                                </td>
                                <td>
                                    <span class="rp-swatch">
                                        <span class="rp-swatch__dot {{ $row['color_class'] }}"></span>{{ $row['color_label'] }}
                                    </span>
                                </td>
                                <td class="text-center"><span class="rp-count">{{ $row['sort_order'] }}</span></td>
                                <td class="text-nowrap">{{ $row['created_at'] }}</td>
                                {{-- Status column = display only; data-order lets the
                                     header sort by state (§3b). --}}
                                <td data-order="{{ $row['enabled'] ? 1 : 0 }}" class="text-center">
                                    <span class="status-pill badge rounded-1 {{ $row['enabled'] ? 'bg-success-subtle' : 'bg-danger-subtle' }}">
                                        {{ $row['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </td>
                                {{-- Action = Edit · switch · Delete. No .form-check/
                                     .form-switch wrapper around the switch: that pulls
                                     the input -2.375rem left (custom.css:107-112) and
                                     breaks the stack (§3b trap 1). --}}
                                <td>
                                    <div class="rp-act-group" role="group" aria-label="Actions for {{ $row['label'] }}">
                                        <button type="button" class="rp-act rp-act--edit rp-edit-card-btn"
                                                data-id="{{ $row['id'] }}"
                                                data-label="{{ $row['label'] }}"
                                                data-icon="{{ $row['icon'] }}"
                                                data-color="{{ $row['color_class'] }}"
                                                data-sort="{{ $row['sort_order'] }}"
                                                title="Edit {{ $row['label'] }}"
                                                aria-label="Edit card {{ $row['label'] }}">
                                            <span class="rp-act__icon"><i class="bi bi-pencil" aria-hidden="true"></i></span>
                                            <span class="rp-act__label">Edit</span>
                                        </button>

                                        <label class="rp-act rp-act--toggle" for="rpCard{{ $row['id'] }}">
                                            <span class="rp-act__icon">
                                                <input class="form-check-input plain-status-toggle card-toggle"
                                                       type="checkbox" role="switch"
                                                       id="rpCard{{ $row['id'] }}"
                                                       data-id="{{ $row['id'] }}"
                                                       data-label="{{ $row['label'] }}"
                                                       @checked($row['enabled'])>
                                            </span>
                                            <span class="rp-act__label">{{ $row['enabled'] ? 'Disable' : 'Enable' }}</span>
                                        </label>

                                        {{-- Delete removes the card from EVERY role, so it
                                             is only offered while this role has it switched
                                             off — mirrored as a disabled stack otherwise. --}}
                                        <span class="rp-act rp-act--del rp-delete-guard{{ $row['enabled'] ? '' : ' d-none' }}"
                                              title="Disable this card for the role before deleting it"
                                              aria-disabled="true">
                                            <span class="rp-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                                            <span class="rp-act__label">Delete</span>
                                        </span>
                                        <button type="button"
                                                class="rp-act rp-act--del rp-delete-card-btn{{ $row['enabled'] ? ' d-none' : '' }}"
                                                data-id="{{ $row['id'] }}"
                                                data-label="{{ $row['label'] }}"
                                                title="Delete {{ $row['label'] }}"
                                                aria-label="Delete card {{ $row['label'] }}">
                                            <span class="rp-act__icon"><i class="bi bi-trash" aria-hidden="true"></i></span>
                                            <span class="rp-act__label">Delete</span>
                                        </button>
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
                     data-dt-footer-for="dashboardCardTable"></div>
            </div>

        </div>
    </div>
</div>

{{-- Add / Edit card — two modals that look alike: same header, tinted field
     card, live preview and footer pair. Only the contents and the submit
     caption differ (§3c). --}}
@foreach ([['add', 'Add New Dashboard Card', 'Create a card for the role dashboard.', 'Save Card'],
           ['edit', 'Edit Dashboard Card', 'Update this card everywhere it is used.', 'Update Card']] as [$mode, $title, $sub, $cta])
<div class="modal fade" id="rp{{ ucfirst($mode) }}CardModal" tabindex="-1"
     aria-labelledby="rp{{ ucfirst($mode) }}CardModalLabel" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rp-modal border-0 shadow">
            <form id="rp{{ ucfirst($mode) }}CardForm" novalidate>
                @csrf
                @if ($mode === 'edit')
                    <input type="hidden" id="rpEditCardId">
                @endif

                <div class="modal-header rp-modal-header">
                    <div>
                        <h5 class="modal-title" id="rp{{ ucfirst($mode) }}CardModalLabel">{{ $title }}</h5>
                        <p class="rp-modal-sub">{{ $sub }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body rp-modal-body">
                    <div class="rp-field-card rp-form-grid">
                        <div class="form-group rp-form-grid--full">
                            <label class="rp-form-label" for="rp{{ ucfirst($mode) }}CardLabel">Label<span class="rp-req">*</span></label>
                            <input type="text" class="form-control rp-control rp-card-label" name="label"
                                   id="rp{{ ucfirst($mode) }}CardLabel"
                                   placeholder="e.g. Pending Leave Applications" autocomplete="off" maxlength="200">
                        </div>

                        <div class="form-group rp-form-grid--full">
                            <label class="rp-form-label" for="rp{{ ucfirst($mode) }}CardIcon">Icon<span class="rp-req">*</span></label>
                            {{-- Intentionally EMPTY apart from the placeholder. The
                                 ~4,200 Material Symbols names are shipped once as JSON
                                 below and handed to Choices.js at open time; rendering
                                 them as <option>s in BOTH modals cost 8,491 elements /
                                 1.26 MB per page load (§3c). --}}
                            <select class="w-100 rp-icon-select" name="icon" id="rp{{ ucfirst($mode) }}CardIcon">
                                <option value="">Select icon…</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="rp-form-label" for="rp{{ ucfirst($mode) }}CardColor">Colour</label>
                            <select class="form-select rp-control rp-card-color" name="color_class"
                                    id="rp{{ ucfirst($mode) }}CardColor">
                                @foreach (\App\Services\RoleService::CARD_COLOURS as $class => $name)
                                    <option value="{{ $class }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="rp-form-label" for="rp{{ ucfirst($mode) }}CardSort">Sort order</label>
                            <input type="number" class="form-control rp-control" name="sort_order"
                                   id="rp{{ ucfirst($mode) }}CardSort" value="{{ $mode === 'add' ? 99 : '' }}" min="1">
                            <p class="rp-form-help">Lower numbers appear first on the dashboard.</p>
                        </div>

                        <div class="form-group rp-form-grid--full">
                            <label class="rp-form-label">Preview</label>
                            <div class="rp-preview-card">
                                <div class="rp-preview-icon stat-icon-blue rp-preview-chip">
                                    <i class="material-icons material-symbols-rounded rp-preview-glyph" aria-hidden="true">apps</i>
                                </div>
                                <div class="rp-preview-body">
                                    <p class="rp-preview-label">Card Label</p>
                                    <p class="rp-preview-count">0</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer rp-modal-footer">
                    <button type="button" class="btn rp-btn-cancel" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn rp-btn-submit">{{ $cta }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endforeach

<!-- Column Visibility Modal -->
<div class="modal fade" id="rpCardColumnVisibilityModal" tabindex="-1"
     aria-labelledby="rpCardColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="rpCardColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="rpCardColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- The icon catalogue, once, as data rather than markup. ~4,200 names ≈ 60 KB
     of JSON against ~900 KB of <option> elements across two modals. --}}
<script type="application/json" id="rpIconNames">@json($materialIcons)</script>
@endsection

@push('scripts')
{{-- Pinned to match the stylesheet — see the note in @push('styles'). --}}
<script src="https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js"></script>
<script>
$(function () {
    'use strict';

    var STORE_URL = "{{ route('dashboard.cards.store') }}";
    var CARD_BASE = "{{ url('dashboard-cards') }}";
    var ASSIGN_URL = "{{ route('assign.roles.dashboard', $role->id) }}";
    var TOKEN = "{{ csrf_token() }}";

    var ICON_NAMES = (function () {
        try { return JSON.parse(document.getElementById('rpIconNames').textContent) || []; }
        catch (e) { return []; }
    })();

    /* Status pills own this; the ext.search predicate below reads it. */
    var rpStatus = '';

    /* ── Status filter ───────────────────────────────────────────────────────
       Enabled/Disabled is the live state of the switch, not table text, so it
       cannot be a column search — it needs a predicate. Registered once and
       scoped by table id so it can't affect other grids. ── */
    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'dashboardCardTable') { return true; }
        if (!rpStatus) { return true; }
        var checked = $(settings.aoData[dataIndex].nTr).find('.card-toggle').is(':checked');
        return rpStatus === 'enabled' ? checked : !checked;
    });

    /* ── DataTable (client-side) ─────────────────────────────────────────────
       Every row is already in the DOM, so DataTables both filters and paginates
       in the browser. No `dom`, `scrollX` or colVis options here — the global
       script owns the chrome and .table-responsive owns the overflow. ── */
    var dt = $('#dashboardCardTable').DataTable({
        autoWidth: false,
        order: [],
        /* footer.blade.php:80 turns the Responsive extension on globally. It
           deals with a table wider than its box by HIDING columns (on a narrow
           screen that takes the Action column away) and swaps in its own +/−
           child-row chrome, which is not this design's. The panel's
           .table-responsive scrolls horizontally instead — §3. */
        responsive: false,
        columnDefs: [
            { orderable: false, targets: [2, 7] },        // Icon, Action
            { searchable: false, targets: [0, 4, 5, 6, 7] }
        ],
        language: {
            emptyTable: '<div class="rp-empty">' +
                '<i class="bi bi-grid-1x2 d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Cards Available</h6>' +
                '<p class="mb-0 small">Get started by adding your first dashboard card.</p>' +
                '</div>',
            zeroRecords: '<div class="rp-empty">' +
                '<i class="bi bi-search d-block mb-2" aria-hidden="true"></i>' +
                '<h6 class="fw-semibold mb-1">No Cards Found</h6>' +
                '<p class="mb-0 small">No card matches your filters.</p>' +
                '</div>'
        }
    });

    /* ── Filters ─────────────────────────────────────────────────────────── */
    $('.rp-status-pill').on('click', function () {
        var $pill = $(this);
        if ($pill.hasClass('active')) { return; }

        $('.rp-status-pill').removeClass('active').attr('aria-pressed', 'false').removeAttr('aria-current');
        $pill.addClass('active').attr({ 'aria-pressed': 'true', 'aria-current': 'true' });

        rpStatus = $pill.attr('data-rp-status') || '';
        dt.draw();
        rpUpdateExportLinks();
    });

    /* ── Column visibility (DataTables column API) ────────────────────────────
       Hidden columns are stored by LABEL, not index: an index would point at a
       different column the moment one is added, silently hiding the wrong one.
       An unknown label is simply ignored, so a renamed column comes back
       visible — the safe direction to fail in. ── */
    var COL_KEY = 'roleDashboardGrid:hiddenColumns:v1';

    /* Header index -> the export key the server understands
       (RoleService::dashboardExportColumnDefs()). Positional: '' marks a column
       that is not in the export at all — here, Action, whose Status twin already
       carries the state.
       ⚠️ Adding a column to the table means adding an entry here too. */
    var RP_EXPORT_COLUMN_KEYS = ['sno', 'label', 'icon', 'color', 'sort_order', 'created_at', 'status', ''];
    var RP_EXPORT_COL_COUNT = RP_EXPORT_COLUMN_KEYS.filter(Boolean).length;

    /* Keep Download and Print carrying exactly the columns still on screen, plus
       the filter and search term currently applied to the grid. */
    function rpUpdateExportLinks() {
        var keys = [];
        dt.columns().every(function () {
            var key = RP_EXPORT_COLUMN_KEYS[this.index()];
            if (key && this.visible()) { keys.push(key); }
        });

        var term = dt.search() || '';

        ['rpDownloadLink', 'rpPrintLink'].forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) { return; }
            var base = link.href.split('?')[0];
            var params = new URLSearchParams(link.href.split('?')[1] || '');

            params.delete('q');
            if (term !== '') { params.set('q', term); }

            params.delete('status');
            if (rpStatus !== '') { params.set('status', rpStatus); }

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
        var $grid = $('#rpCardColumnToggleGrid');
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

            var inputId = 'rpcardcolvis_' + index;
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

    function rpUpdateCounts() {
        /* ⚠️ Count through the DataTables row set, NOT the DOM. A paginated
           client-side table keeps only the current page's <tr>s in the tbody, so
           a DOM count would read "All 10" the moment anyone toggled something. */
        var $toggles = dt.rows().nodes().to$().find('.card-toggle');
        var total = $toggles.length;
        var enabled = $toggles.filter(':checked').length;

        $('.rp-status-pill[data-rp-status=""] .rp-pill-count').text(total.toLocaleString());
        $('.rp-status-pill[data-rp-status="enabled"] .rp-pill-count').text(enabled.toLocaleString());
        $('.rp-status-pill[data-rp-status="disabled"] .rp-pill-count').text((total - enabled).toLocaleString());
    }

    /* ── Enable / disable a card for this role ───────────────────────────────
       The switch is the control; the Status badge one column over is the
       display. Repaint both from the row, and swap the Delete control: deleting
       removes the card from EVERY role, so it is only offered while this role
       has the card switched off. ── */
    function paintCardRow($checkbox, enabled) {
        var $row = $checkbox.closest('tr');

        $row.find('.status-pill')
            .toggleClass('bg-success-subtle', enabled)
            .toggleClass('bg-danger-subtle', !enabled)
            .text(enabled ? 'Enabled' : 'Disabled');

        // Keep the sort key in step with the badge, or ordering by Status goes
        // stale the moment someone toggles a row.
        $row.find('td[data-order]').attr('data-order', enabled ? 1 : 0);

        // The caption names the ACTION, not the state (§3b).
        $checkbox.closest('.rp-act').find('.rp-act__label').text(enabled ? 'Disable' : 'Enable');

        $row.find('.rp-delete-card-btn').toggleClass('d-none', enabled);
        $row.find('.rp-delete-guard').toggleClass('d-none', !enabled);

        rpUpdateCounts();
    }

    $(document).on('change', '.card-toggle', function () {
        var $checkbox = $(this);
        var enabled = $checkbox.is(':checked');

        $.ajax({
            url: ASSIGN_URL,
            type: 'POST',
            data: { _token: TOKEN, card_id: $checkbox.data('id'), status: enabled ? 1 : 0 },
            success: function (response) {
                if (response && response.success) {
                    paintCardRow($checkbox, enabled);
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

    /* ══ Choices.js icon selects ════════════════════════════════════════════
       Both live inside a modal, so they are built on `shown.bs.modal` (a Choices
       dropdown created while the modal is hidden measures wrong) and torn down
       on `hidden.bs.modal`. ── */

    function destroyChoices(el) {
        if (el && el._rpChoices) {
            try { el._rpChoices.destroy(); } catch (e) { /* noop */ }
            el._rpChoices = null;
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
                '<span class="rp-icon-option">' +
                '<i class="material-icons material-symbols-rounded rp-icon-glyph">' + value + '</i>' +
                '<span class="rp-icon-label">' + escapeHtml(data.label) + '</span>' +
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

    /* The whole catalogue as Choices' own data. No `selected` flags and no
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

    /* ⚠️ Apply the value AFTER constructing, through Choices' own API. Neither
       `el.value = x` before `new Choices(...)` nor a `selected: true` flag in
       the `choices` array makes the widget RENDER the selection: the native
       <select> ends up right while the box still reads "Select icon…". */
    function applyChoiceValue(el, value) {
        if (!el || !el._rpChoices || !value) { return; }
        try { el._rpChoices.setChoiceByValue(String(value)); } catch (e) { /* not in list */ }
    }

    function buildIconChoices($modal, selected) {
        var el = $modal.find('.rp-icon-select')[0];
        if (!el) { return; }

        destroyChoices(el);

        if (typeof window.Choices === 'undefined') {
            // CDN blocked: fall back to a native select, filled so it still works.
            $(el).addClass('form-select rp-control');
            if (el.options.length <= 1) {
                var frag = document.createDocumentFragment();
                ICON_NAMES.forEach(function (n) { frag.appendChild(new Option(n, n)); });
                el.appendChild(frag);
            }
            el.value = selected || '';
            syncPreview($modal);
            return;
        }

        el._rpChoices = new Choices(el, {
            removeItemButton: false,
            shouldSort: false,
            searchEnabled: true,
            searchPlaceholderValue: 'Search icons…',
            placeholder: true,
            itemSelectText: '',
            allowHTML: true,
            shouldFlip: true,
            // ~4,200 icons: render a window of them, not all of them, or the
            // dropdown pins the main thread every time it opens. Search narrows
            // the list, so the cap is never a dead end.
            renderChoiceLimit: 100,
            searchResultLimit: 100,
            choices: iconChoiceList(selected),
            callbackOnCreateTemplates: iconTemplates
        });

        applyChoiceValue(el, selected);
        // Bind AFTER prefilling — setChoiceByValue fires `change`.
        $(el).off('change.rp').on('change.rp', function () { syncPreview($modal); });

        syncPreview($modal);
    }

    /* ── Live preview ─────────────────────────────────────────────────────── */
    function syncPreview($modal) {
        var label = $modal.find('.rp-card-label').val() || 'Card Label';
        var icon = $modal.find('.rp-icon-select').val() || 'apps';
        var color = $modal.find('.rp-card-color').val() || 'stat-icon-blue';

        $modal.find('.rp-preview-label').text(label);
        $modal.find('.rp-preview-glyph').text(icon);
        $modal.find('.rp-preview-chip').attr('class', 'rp-preview-icon rp-preview-chip ' + color);
    }

    $(document).on('input', '.rp-card-label', function () { syncPreview($(this).closest('.modal')); });
    $(document).on('change', '.rp-card-color', function () { syncPreview($(this).closest('.modal')); });

    /* ── Add modal ────────────────────────────────────────────────────────── */
    var $addModal = $('#rpAddCardModal');
    var $editModal = $('#rpEditCardModal');
    var pendingEditIcon = '';

    $('#rpAddCardBtn').on('click', function () {
        var $form = $('#rpAddCardForm');
        $form[0].reset();
        $form.find('.is-invalid').removeClass('is-invalid');
        bootstrap.Modal.getOrCreateInstance($addModal[0]).show();
    });

    $addModal.on('shown.bs.modal', function () { buildIconChoices($addModal, ''); })
             .on('hidden.bs.modal', function () {
                 destroyChoices($addModal.find('.rp-icon-select')[0]);
                 $addModal.find('.rp-icon-select').off('change.rp');
             });

    $editModal.on('shown.bs.modal', function () { buildIconChoices($editModal, pendingEditIcon); })
              .on('hidden.bs.modal', function () {
                  destroyChoices($editModal.find('.rp-icon-select')[0]);
                  $editModal.find('.rp-icon-select').off('change.rp');
                  pendingEditIcon = '';
              });

    $(document).on('click', '.rp-edit-card-btn', function () {
        var $btn = $(this);

        $('#rpEditCardId').val($btn.attr('data-id'));
        $('#rpEditCardLabel').val($btn.attr('data-label'));
        $('#rpEditCardColor').val($btn.attr('data-color') || 'stat-icon-blue');
        $('#rpEditCardSort').val($btn.attr('data-sort'));
        // The Choices instance is built in shown.bs.modal, so stash the value
        // for it rather than assigning to a widget that does not exist yet.
        pendingEditIcon = $btn.attr('data-icon') || '';

        syncPreview($editModal);
        $editModal.find('.rp-preview-glyph').text(pendingEditIcon || 'apps');

        bootstrap.Modal.getOrCreateInstance($editModal[0]).show();
    });

    /* ── Add / Update submit ──────────────────────────────────────────────── */
    function busy($btn, on, caption) {
        if (on) {
            $btn.data('caption', $btn.html()).prop('disabled', true).html(
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                '<span>' + caption + '</span>'
            );
        } else {
            $btn.prop('disabled', false).html($btn.data('caption'));
        }
    }

    function cardPayload($modal) {
        return {
            label: ($modal.find('.rp-card-label').val() || '').trim(),
            icon: $modal.find('.rp-icon-select').val() || '',
            color_class: $modal.find('.rp-card-color').val(),
            sort_order: $modal.find('input[name="sort_order"]').val()
        };
    }

    function reportAjaxError(xhr) {
        if (typeof toastr === 'undefined') { return; }
        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
            $.each(xhr.responseJSON.errors, function (field, msgs) { toastr.error(msgs[0]); });
        } else {
            toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong');
        }
    }

    $('#rpAddCardForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $addModal.find('.rp-btn-submit');
        var data = cardPayload($addModal);

        if (!data.label) { if (typeof toastr !== 'undefined') { toastr.error('Label is required.'); } return; }
        if (!data.icon) { if (typeof toastr !== 'undefined') { toastr.error('Please select an icon.'); } return; }

        busy($btn, true, 'Saving…');

        $.ajax({
            url: STORE_URL,
            type: 'POST',
            data: $.extend({ _token: TOKEN }, data),
            success: function (response) {
                if (!response || !response.success) {
                    if (typeof toastr !== 'undefined') { toastr.error((response && response.message) || 'Something went wrong'); }
                    return;
                }
                if (typeof toastr !== 'undefined') { toastr.success(response.message); }
                bootstrap.Modal.getOrCreateInstance($addModal[0]).hide();
                // A new card starts unassigned, and the row markup is involved
                // enough (badge + three action stacks) that rebuilding it here
                // would be a second source of truth. Reload instead.
                window.location.reload();
            },
            error: reportAjaxError,
            complete: function () { busy($btn, false); }
        });
    });

    $('#rpEditCardForm').on('submit', function (e) {
        e.preventDefault();
        var $btn = $editModal.find('.rp-btn-submit');
        var id = $('#rpEditCardId').val();
        var data = cardPayload($editModal);

        if (!data.label) { if (typeof toastr !== 'undefined') { toastr.error('Label is required.'); } return; }
        if (!data.icon) { if (typeof toastr !== 'undefined') { toastr.error('Please select an icon.'); } return; }

        busy($btn, true, 'Updating…');

        $.ajax({
            url: CARD_BASE + '/' + id,
            type: 'POST',
            data: $.extend({ _token: TOKEN, _method: 'PUT' }, data),
            success: function (response) {
                if (!response || !response.success) {
                    if (typeof toastr !== 'undefined') { toastr.error((response && response.message) || 'Something went wrong'); }
                    return;
                }
                if (typeof toastr !== 'undefined') { toastr.success(response.message); }
                bootstrap.Modal.getOrCreateInstance($editModal[0]).hide();

                var card = response.card;
                var $row = $('#dashboardCardTable tbody tr[data-id="' + card.id + '"]');
                $row.find('td:eq(1)').text(card.label);
                $row.find('.rp-card-icon')
                    .attr('class', 'rp-card-icon ' + card.color_class)
                    .attr('title', card.icon)
                    .find('i').text(card.icon);
                $row.find('.rp-swatch__dot').attr('class', 'rp-swatch__dot ' + card.color_class);
                $row.find('.rp-swatch').contents().last().replaceWith(
                    document.createTextNode(response.color_label || $editModal.find('.rp-card-color option:selected').text())
                );
                $row.find('.rp-count').text(card.sort_order);
                $row.find('.rp-edit-card-btn')
                    .attr({ 'data-label': card.label, 'data-icon': card.icon,
                            'data-color': card.color_class, 'data-sort': card.sort_order });
                $row.find('.rp-delete-card-btn').attr('data-label', card.label);
                // DataTables caches cell text for search/sort — tell it the row changed.
                dt.row($row).invalidate().draw(false);
            },
            error: reportAjaxError,
            complete: function () { busy($btn, false); }
        });
    });

    /* ── Delete a card (removes it from every role) ───────────────────────── */
    $(document).on('click', '.rp-delete-card-btn', function () {
        var $btn = $(this);
        var id = $btn.attr('data-id');
        var label = $btn.attr('data-label') || 'this card';

        function send() {
            $btn.prop('disabled', true);
            $.ajax({
                url: CARD_BASE + '/' + id,
                type: 'POST',
                data: { _token: TOKEN, _method: 'DELETE' },
                success: function (response) {
                    if (response && response.success) {
                        if (typeof toastr !== 'undefined') { toastr.success(response.message); }
                        dt.row($('#dashboardCardTable tbody tr[data-id="' + id + '"]')).remove().draw(false);
                        rpUpdateCounts();
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error((response && response.message) || 'Something went wrong');
                    }
                },
                error: function () {
                    if (typeof toastr !== 'undefined') { toastr.error('Something went wrong'); }
                },
                complete: function () { $btn.prop('disabled', false); }
            });
        }

        if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') {
            if (window.confirm('Delete "' + label + '"? It will be removed from all roles.')) { send(); }
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Delete "' + label + '"? It will be removed from all roles.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d92d20',
            reverseButtons: true
        }).then(function (result) {
            if (result.isConfirmed) { send(); }
        });
    });
});
</script>
@endpush
