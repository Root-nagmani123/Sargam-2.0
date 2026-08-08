@extends('admin.layouts.master')

@section('title', 'LBSNAA Directory')

@section('content')
@php
    // Server-side filters that must survive on the download links. The search
    // term isn't here: it's the DataTables filter now, and the JS stamps it on.
    $lbsFilters = [
        'status' => $status !== 'active' ? $status : null,
        'department_id' => $selectedDepartment ?: null,
        'designation_id' => $selectedDesignation ?: null,
    ];
    $lbsExportParams = array_filter($lbsFilters, fn ($value) => filled($value));
    $lbsExportUrl = fn (string $format) => route('admin.directory.lbsnaa', $lbsExportParams + ['export' => $format]);

    // Print is its own button; the rest live in the Download dropdown. Print,
    // PDF and Excel share one branded report layout and CSV is the flat
    // machine-readable file — but all four honour the same filters and the
    // same Columns choice (see LBS_DOWNLOAD_LINK_IDS).
    $lbsPrintLinkId = 'lbsDownloadPrintLink';
    $lbsDownloads = [
        ['id' => 'lbsDownloadPdfLink', 'format' => 'pdf', 'label' => 'PDF', 'icon' => 'bi-file-earmark-pdf'],
        ['id' => 'lbsDownloadExcelLink', 'format' => 'excel', 'label' => 'Excel (.xlsx)', 'icon' => 'bi-file-earmark-excel'],
        ['id' => 'lbsDownloadCsvLink', 'format' => 'csv', 'label' => 'CSV', 'icon' => 'bi-filetype-csv'],
    ];

    // Switching tab starts a clean slate: a Section/Designation from the other
    // bucket is rejected server-side anyway, and a stale search reads as a bug.
    $lbsTabs = [
        'active' => ['label' => 'Active', 'url' => route('admin.directory.lbsnaa')],
        'inactive' => ['label' => 'Inactive', 'url' => route('admin.directory.lbsnaa', ['status' => 'inactive'])],
    ];
@endphp

<div class="container-fluid lbs-page">
    <x-breadcrum title="LBSNAA Directory"></x-breadcrum>

    {{-- Status pills + Print/Download sit ABOVE the card (new-design chrome).
         Active = employee_master.status 1, matching EmployeeMaster::scopeActive(). --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter staff by status">
            @foreach($lbsTabs as $tabKey => $tab)
                <li class="nav-item" role="presentation">
                    {{-- A real link, not a JS button: this page filters on the server,
                         so the tab is just another URL. --}}
                    <a href="{{ $tab['url'] }}"
                        class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $status === $tabKey ? 'active' : '' }}"
                        @if($status === $tabKey) aria-current="page" @endif>{{ $tab['label'] }}</a>
                </li>
            @endforeach
        </ul>

        <div class="d-flex flex-wrap align-items-center gap-2">

            <div class="dropdown">
                <button type="button" id="lbsDownloadToggle" class="btn lbs-download-btn border-0 dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" {{ $employees->count() ? '' : 'disabled' }}>
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="lbsDownloadToggle">
                    @foreach($lbsDownloads as $download)
                        <li>
                            <a href="{{ $lbsExportUrl($download['format']) }}"
                                id="{{ $download['id'] }}" class="dropdown-item">
                                <i class="bi {{ $download['icon'] }} me-1" aria-hidden="true"></i> {{ $download['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            {{-- Print opens the report sheet in a new tab, which pops its own print
                 dialog — going in place would lose the directory you came from. --}}
            <a href="{{ $lbsExportUrl('print') }}" id="{{ $lbsPrintLinkId }}"
                class="btn lbs-download-btn border-0 {{ $employees->count() ? '' : 'disabled' }}"
                target="_blank" rel="noopener"
                @if(! $employees->count()) aria-disabled="true" tabindex="-1" @endif>
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- Toolbar: filters left, Columns + search right. The whole toolbar is one
                 GET form — this page filters server-side, so every control submits it. --}}
            <form method="GET" action="{{ route('admin.directory.lbsnaa') }}" id="lbsFilterForm"
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                {{-- Carried so a filter submit doesn't silently drop the active tab. --}}
                <input type="hidden" name="status" value="{{ $status }}">

                <div class="d-flex flex-wrap align-items-center gap-3 lbs-toolbar-filters">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select name="department_id" class="form-select js-lbs-filter" aria-label="Section">
                            <option value="">Section</option>
                            @foreach($departments as $pk => $name)
                                <option value="{{ $pk }}" title="{{ $name }}"
                                    {{ $selectedDepartment === (int) $pk ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="programme-dt-filter-select">
                        <select name="designation_id" class="form-select js-lbs-filter" aria-label="Designation">
                            <option value="">Designation</option>
                            @foreach($designations as $pk => $name)
                                <option value="{{ $pk }}" title="{{ $name }}"
                                    {{ $selectedDesignation === (int) $pk ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Reset clears the filters but stays on the current tab. --}}
                    <a href="{{ $lbsTabs[$status]['url'] }}"
                        class="btn programme-dt-btn-reset d-inline-flex align-items-center">Reset Filters</a>
                </div>

                {{-- Two filter selects + Reset leave ~420px for this group; without
                     the nowrap the search wrapped onto a second toolbar row. --}}
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto lbs-toolbar-actions">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnLbsColumns"
                        data-bs-toggle="modal" data-bs-target="#lbsColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Empty slot: datatable-global-ui.js moves the DataTables filter
                         in here, so the search is instant and needs no markup of ours. --}}
                    <div class="programme-dt-search" data-dt-search-for="lbsnaaDirectoryTable"></div>
                </div>
            </form>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- DataTables sorts, searches and pages this table client-side;
                         datatable-global-ui.js fills the search slot and the footer. --}}
                    <table id="lbsnaaDirectoryTable"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table">
                        <thead>
                            <tr>
                                {{-- The photo rides in the Name cell as an avatar rather than
                                     taking a column of its own. Header labels match
                                     DirectoryController::LBSNAA_EXPORT_COLUMNS. --}}
                                <th>S. No.</th>
                                <th>Name</th>
                                <th>Designation</th>
                                <th>Section</th>
                                <th>Address</th>
                                <th>Office Extension</th>
                                <th>Mobile</th>
                                <th>Residence No.</th>
                                <th>Email ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($employees as $index => $employee)
                                @php
                                    $lbsPhoto = !empty($employee->profile_picture)
                                        ? asset('storage/' . $employee->profile_picture)
                                        : null;
                                    $lbsAddress = trim((string) $employee->current_address);
                                    $lbsDesignation = trim((string) $employee->designation_name);
                                    $lbsSection = trim((string) $employee->department_name);
                                @endphp
                                <tr>
                                    {{-- Placeholder only: renumbered on every draw so the
                                         serial follows the current sort and page. --}}
                                    <td>{{ $index + 1 }}</td>
                                    {{-- data-order / data-search keep the avatar's initials out
                                         of the sort and search keys: without them DataTables reads
                                         the cell as "AK AAKANKSHA KULSHRESTHA". --}}
                                    <td data-order="{{ $employee->full_name }}" data-search="{{ $employee->full_name }}">
                                        {{-- Initials sit underneath; the photo is layered over
                                             them and only revealed once it actually decodes, so
                                             a 404 or a missing profile_picture leaves the
                                             monogram showing, not a broken-image glyph. --}}
                                        <span class="lbs-name-cell">
                                            <span class="lbs-avatar" aria-hidden="true">
                                                @if($employee->initials !== '')
                                                    <span class="lbs-avatar-initials">{{ $employee->initials }}</span>
                                                @else
                                                    <i class="bi bi-person lbs-avatar-icon"></i>
                                                @endif
                                                @if($lbsPhoto)
                                                    {{-- data-src, not src: every row is in the DOM up
                                                         front, so a real src would fire one request per
                                                         record. The JS promotes it per drawn page. --}}
                                                    <img data-src="{{ $lbsPhoto }}" alt="" class="lbs-avatar-img"
                                                        loading="lazy" decoding="async">
                                                @endif
                                            </span>
                                            <span class="lbs-name">{{ $employee->full_name ?: '-' }}</span>
                                        </span>
                                    </td>
                                    {{-- Designation / Section / Address are free text and run
                                         long; clamped to one line with the full value on hover,
                                         or a single record wraps the row to a dozen lines. --}}
                                    <td>
                                        @if($lbsDesignation !== '')
                                            <span class="lbs-clamp lbs-clamp--designation" title="{{ $lbsDesignation }}">{{ $lbsDesignation }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($lbsSection !== '')
                                            <span class="lbs-clamp lbs-clamp--section" title="{{ $lbsSection }}">{{ $lbsSection }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @if($lbsAddress !== '')
                                            <span class="lbs-clamp lbs-clamp--address" title="{{ $lbsAddress }}">{{ $lbsAddress }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $employee->office_extension_no ?: '-' }}</td>
                                    <td>{{ $employee->mobile ?: '-' }}</td>
                                    <td>{{ $employee->residence_no ?: '-' }}</td>
                                    <td>{{ $employee->contact_email ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer variant A — left empty; datatable-global-ui.js fills it with the
                 pager and "Showing [10] of N items". --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3"
                data-dt-footer-for="lbsnaaDirectoryTable"></div>
        </div>
    </div>
</div>

{{-- Column visibility (colvis-item card grid — the programme-dt variant).
     #lbsnaaColumnToggleGrid is registered in custom.css alongside the other grids. --}}
<div class="modal fade" id="lbsColumnVisibilityModal" tabindex="-1" aria-labelledby="lbsColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="lbsColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="lbsnaaColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Everything here is namespaced under .lbs-page so it can't leak into the ~400
   other admin views. Values come from the --ds-* token layer (docs/design.md). */

/* Print + Download — same shape as the Attendance page's download control.
   Print is an <a>, Download a <button>, so the box model is set explicitly
   rather than relying on button defaults. */
.lbs-page .lbs-download-btn {
    height: 40px;
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2, 0.5rem);
    padding: 0 1.1rem;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--ds-primary, #004a93);
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    background: var(--ds-surface, #fff);
}

.lbs-page .lbs-download-btn:hover:not(:disabled):not(.disabled) {
    background: #f2f7fc;
    border-color: var(--ds-primary, #004a93);
    color: var(--ds-primary, #004a93);
}

.lbs-page .lbs-download-btn:disabled,
.lbs-page .lbs-download-btn.disabled {
    color: #98a2b3;
    opacity: 1;
    pointer-events: none;
}

.lbs-page .lbs-download-btn i {
    font-size: 1rem;
    line-height: 1;
}

/* Name cell = avatar + name. The old markup used a .directory-photo class that
   had no size rule anywhere in the app, so photos rendered at natural size. */
.lbs-page .lbs-name-cell {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2, 0.5rem);
}

.lbs-page .lbs-avatar {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex-shrink: 0;
    overflow: hidden;
    border-radius: 50%;
    background: rgba(0, 74, 147, 0.08);
    color: var(--ds-primary, #004a93);
}

.lbs-page .lbs-avatar-initials {
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.lbs-page .lbs-avatar-icon {
    font-size: 1.125rem;
    line-height: 1;
    opacity: 0.7;
}

.lbs-page .lbs-avatar-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.lbs-page .lbs-avatar-img.is-loaded {
    opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
    .lbs-page .lbs-avatar-img {
        transition: none;
    }
}

.lbs-page .lbs-name {
    color: #101828;
    font-weight: 500;
}

/* Column 2 is the identity cell. The shared .programme-dt-table rule sets it to
   wrap at 420px, which breaks two-word names across lines when space is tight. */
.lbs-page .programme-dt-table tbody td:nth-child(2) {
    max-width: none;
    white-space: nowrap;
}

/* Designation / Section / Address are free text and run long — without the
   clamp one record wraps its whole row to a dozen lines. Full value on hover. */
.lbs-page .lbs-clamp {
    display: inline-block;
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
    cursor: help;
    text-decoration: underline dotted;
    text-underline-offset: 0.15em;
}

.lbs-page .lbs-clamp--designation { max-width: 190px; }
.lbs-page .lbs-clamp--section { max-width: 165px; }
.lbs-page .lbs-clamp--address { max-width: 240px; }

@media (min-width: 992px) {
    /* Two 180px selects + Reset + a 300px search overrun the 952px content area
       by ~60px. Pin the filter group so it can't wrap to a second row, and let
       the search absorb the difference by shrinking instead. */
    .lbs-page .lbs-toolbar-filters {
        flex-wrap: nowrap !important;
        flex-shrink: 0;
    }

    .lbs-page .lbs-toolbar-actions {
        flex-wrap: nowrap !important;
        min-width: 0;
    }

    .lbs-page .lbs-toolbar-actions .programme-dt-search {
        flex: 0 1 300px;
        min-width: 190px;
    }
}

@media (max-width: 767.98px) {
    .lbs-page .lbs-avatar {
        width: 32px;
        height: 32px;
    }

    .lbs-page .lbs-avatar-initials {
        font-size: 0.6875rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(function () {
    'use strict';

    var $table = $('#lbsnaaDirectoryTable');
    var form = document.getElementById('lbsFilterForm');
    if (!$table.length || !form) return;

    // Header index -> the export column key(s) that column stands for, matching
    // DirectoryController::LBSNAA_EXPORT_COLUMNS. Positional — adding a <th>
    // means editing this array.
    var LBS_EXPORT_COLUMN_KEYS = [
        ['sno'], ['name'], ['designation'], ['section'], ['address'],
        ['office_ext'], ['mobile'], ['residence'], ['email']
    ];
    var LBS_EXPORT_COL_COUNT = LBS_EXPORT_COLUMN_KEYS.reduce(function (n, keys) { return n + keys.length; }, 0);
    // Print lives outside the dropdown but still tracks the visible columns.
    var LBS_DOWNLOAD_LINK_IDS = @json(array_merge([$lbsPrintLinkId], array_column($lbsDownloads, 'id')));

    // Keyed by user so two people sharing a browser don't inherit each other's
    // hidden columns. Stores LABELS, not indices — a column inserted mid-table
    // would silently shift every saved index (see docs/column-visibility.md).
    var STORAGE_KEY = 'sargam.lbsnaaDirectory.hiddenCols.{{ auth()->id() ?? 'guest' }}';

    function readHidden() {
        try {
            var raw = window.localStorage.getItem(STORAGE_KEY);
            var arr = raw ? JSON.parse(raw) : [];
            return Array.isArray(arr) ? arr : [];
        } catch (e) {
            return []; // private mode / storage disabled / corrupt value
        }
    }

    function saveHidden(hidden) {
        try {
            window.localStorage.setItem(STORAGE_KEY, JSON.stringify(hidden));
        } catch (e) { /* storage unavailable — the choice just won't persist */ }
    }

    // ── Filters (still server-side: they change which rows are loaded) ───────
    form.querySelectorAll('.js-lbs-filter').forEach(function (select) {
        select.addEventListener('change', function () { form.submit(); });
    });

    // ── DataTable ───────────────────────────────────────────────────────────
    // dom / language / pageLength / pagingType all come from datatable-global-ui.js,
    // which also moves the filter into .programme-dt-search and builds the footer.
    var dt = $table.DataTable({
        order: [[1, 'asc']],          // Name, matching the server's sort
        // The Responsive extension is loaded globally and self-starts; it stamps a
        // ► expand control onto the first column and collapses cells we already
        // handle with .table-responsive horizontal scrolling.
        responsive: false,
        columnDefs: [
            // S. No. is a running count, not data: never sort or search on it.
            { targets: 0, orderable: false, searchable: false }
        ],
        language: {
            searchPlaceholder: 'Search name, email, mobile, section',
            emptyTable: 'No staff records in this view',
            zeroRecords: 'No staff match your search'
        }
    });

    // S. No. follows the current sort and page rather than the original row order.
    function renumberSerial() {
        var start = dt.page.info().start;
        dt.column(0, { search: 'applied', order: 'applied', page: 'current' }).nodes().each(function (cell, i) {
            cell.textContent = start + i + 1;
        });
    }

    // ── Avatars ─────────────────────────────────────────────────────────────
    // Reveal a photo only once it has actually decoded; drop it on error so the
    // initials underneath stay visible. Re-run per draw: DataTables detaches the
    // rows that aren't on the current page, so images settle as pages are shown.
    function settleAvatar(img) {
        if (img.naturalWidth > 0) {
            img.classList.add('is-loaded');
        } else {
            img.remove();
        }
    }

    // load/error don't bubble, hence capture.
    $table[0].addEventListener('load', function (e) {
        if (e.target.classList && e.target.classList.contains('lbs-avatar-img')) settleAvatar(e.target);
    }, true);
    $table[0].addEventListener('error', function (e) {
        if (e.target.classList && e.target.classList.contains('lbs-avatar-img')) settleAvatar(e.target);
    }, true);

    function settleVisibleAvatars() {
        $table[0].querySelectorAll('.lbs-avatar-img').forEach(function (img) {
            // Promote data-src -> src for the rows actually on screen. Only these
            // ever hit the network; the rest of the set stays request-free.
            if (!img.getAttribute('src') && img.dataset.src) {
                img.src = img.dataset.src;
                return; // load/error will call back through the capture listeners
            }
            if (img.complete) settleAvatar(img);
        });
    }

    // ── Downloads follow what's on screen ───────────────────────────────────
    var labels = dt.columns().header().toArray().map(function (th) {
        return th.textContent.replace(/\s+/g, ' ').trim();
    });

    function syncDownloadLinks() {
        var hidden = readHidden();
        var keys = [];
        labels.forEach(function (label, i) {
            if (hidden.indexOf(label) !== -1) return;
            (LBS_EXPORT_COLUMN_KEYS[i] || []).forEach(function (key) { keys.push(key); });
        });

        var term = dt.search();

        LBS_DOWNLOAD_LINK_IDS.forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) return;
            var url = new URL(link.href, window.location.origin);
            // Omit either param entirely when it isn't narrowing anything — the
            // server reads "no cols" as every column and "no search" as every row.
            if (keys.length && keys.length !== LBS_EXPORT_COL_COUNT) {
                url.searchParams.set('cols', keys.join(','));
            } else {
                url.searchParams.delete('cols');
            }
            if (term) {
                url.searchParams.set('search', term);
            } else {
                url.searchParams.delete('search');
            }
            link.href = url.toString();
        });
    }

    // ── Column visibility ───────────────────────────────────────────────────
    function applyHidden() {
        var hidden = readHidden();
        dt.columns().every(function () {
            this.visible(hidden.indexOf(labels[this.index()]) === -1, false);
        });
        dt.columns.adjust();
    }

    function buildGrid() {
        var grid = document.getElementById('lbsnaaColumnToggleGrid');
        if (!grid) return;
        var hidden = readHidden();
        grid.innerHTML = '';

        labels.forEach(function (label, i) {
            if (!label) return;

            var inputId = 'lbsColvis_' + i;
            var cell = document.createElement('div');
            cell.className = 'col-12 col-sm-6 col-md-4';

            var wrap = document.createElement('label');
            wrap.className = 'colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100';
            wrap.setAttribute('for', inputId);
            wrap.title = label;

            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.className = 'form-check-input m-0';
            cb.id = inputId;
            cb.checked = hidden.indexOf(label) === -1;

            cb.addEventListener('change', function () {
                var current = readHidden();
                var pos = current.indexOf(label);
                if (this.checked) {
                    if (pos !== -1) current.splice(pos, 1);
                } else if (pos === -1) {
                    current.push(label);
                }
                saveHidden(current);
                dt.column(i).visible(this.checked, false);
                dt.columns.adjust();
                syncDownloadLinks();
            });

            var text = document.createElement('span');
            text.textContent = label; // .textContent, not innerHTML — labels are DOM text

            wrap.appendChild(cb);
            wrap.appendChild(text);
            cell.appendChild(wrap);
            grid.appendChild(cell);
        });
    }

    dt.on('draw.dt', function () {
        renumberSerial();
        settleVisibleAvatars();
        stampSearchPlaceholder();
    });
    dt.on('search.dt', syncDownloadLinks);

    // datatable-global-ui.js resolves language before our init options merge and
    // moves the filter into the slot after init, so searchPlaceholder can only be
    // set on the moved input — from the draw handler, once it exists.
    var SEARCH_PLACEHOLDER = 'Search name, email, mobile, section';
    function stampSearchPlaceholder() {
        var input = document.querySelector('.programme-dt-search input');
        if (input && input.getAttribute('placeholder') !== SEARCH_PLACEHOLDER) {
            input.setAttribute('placeholder', SEARCH_PLACEHOLDER);
        }
    }

    applyHidden();
    renumberSerial();
    settleVisibleAvatars();
    syncDownloadLinks();
    stampSearchPlaceholder();
    buildGrid();
});
</script>
@endpush

@endsection
