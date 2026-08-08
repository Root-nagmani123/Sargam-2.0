@extends('admin.layouts.master')

@section('title', 'OT Directory')

@section('content')
@php
    // Filters that must survive on the Download links and the rows-per-page jump.
    $otFilters = [
        'status' => $status !== 'active' ? $status : null,
        'course_id' => $selectedCourseId ?: null,
        'search' => $search ?: null,
        'per_page' => $perPage !== '10' ? $perPage : null,
    ];
    $otExportParams = array_filter($otFilters, fn ($value) => filled($value));
    $otExportUrl = fn (string $format) => route('admin.directory.ot', $otExportParams + ['export' => $format]);

    // Print is its own button; the rest live in the Download dropdown. Print,
    // PDF and Excel share one branded report layout and CSV is the flat
    // machine-readable file — but all four honour the same filters and the
    // same Columns choice (see OT_DOWNLOAD_LINK_IDS).
    $otPrintLinkId = 'otDownloadPrintLink';
    $otDownloads = [
        ['id' => 'otDownloadPdfLink', 'format' => 'pdf', 'label' => 'PDF', 'icon' => 'bi-file-earmark-pdf'],
        ['id' => 'otDownloadExcelLink', 'format' => 'excel', 'label' => 'Excel (.xlsx)', 'icon' => 'bi-file-earmark-excel'],
        ['id' => 'otDownloadCsvLink', 'format' => 'csv', 'label' => 'CSV', 'icon' => 'bi-filetype-csv'],
    ];

    // Switching tab starts a clean slate: a course_id from the other bucket is
    // rejected server-side anyway, and a stale search would look like a bug.
    $otTabs = [
        'active' => ['label' => 'Active', 'url' => route('admin.directory.ot')],
        'archive' => ['label' => 'Archived', 'url' => route('admin.directory.ot', ['status' => 'archive'])],
    ];
@endphp

<div class="container-fluid ot-page">
    <x-breadcrum title="OT Directory"></x-breadcrum>

    {{-- Status pills + Download sit ABOVE the card (new-design chrome).
         Same Active/Archived split as Course Master, driven by course end_date. --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white mb-0"
            role="group" aria-label="Filter programmes by status">
            @foreach($otTabs as $tabKey => $tab)
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
            {{-- Print stands on its own next to Download. It opens the report sheet
                 in a new tab, which pops its own print dialog — going in place
                 would lose the directory you came from. --}}
            <a href="{{ $otExportUrl('print') }}" id="{{ $otPrintLinkId }}"
                class="btn ot-download-btn {{ $students->total() ? '' : 'disabled' }}"
                target="_blank" rel="noopener"
                @if(! $students->total()) aria-disabled="true" tabindex="-1" @endif>
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </a>

            <div class="dropdown">
                <button type="button" id="otDownloadToggle" class="btn ot-download-btn border-0 dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false" {{ $students->total() ? '' : 'disabled' }}>
                    <i class="bi bi-download" aria-hidden="true"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="otDownloadToggle">
                    @foreach($otDownloads as $download)
                        <li>
                            <a href="{{ $otExportUrl($download['format']) }}"
                                id="{{ $download['id'] }}" class="dropdown-item">
                                <i class="bi {{ $download['icon'] }} me-1" aria-hidden="true"></i> {{ $download['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">

            {{-- Toolbar: filters left, Columns + search right. The whole toolbar is one
                 GET form — this page filters server-side, so both controls submit it. --}}
            <form method="GET" action="{{ route('admin.directory.ot') }}" id="otFilterForm"
                class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-4 programme-dt-toolbar">
                {{-- Carried so a filter submit doesn't silently drop the active tab
                     or the footer's rows-per-page choice. --}}
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    <div class="programme-dt-filter-select">
                        <select name="course_id" class="form-select" id="otCourseSelect" aria-label="Program Name">
                            <option value="">Program Name</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->pk }}"
                                    title="{{ $course->course_name }}"
                                    {{ (int) $selectedCourseId === (int) $course->pk ? 'selected' : '' }}>
                                    {{ $course->couse_short_name ?: $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Reset clears the filters but stays on the current tab. --}}
                    <a href="{{ $otTabs[$status]['url'] }}"
                        class="btn programme-dt-btn-reset d-inline-flex align-items-center">Reset Filters</a>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns" id="btnOtColumns"
                        data-bs-toggle="modal" data-bs-target="#otColumnVisibilityModal"
                        title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>

                    {{-- Server-side search: a real input (not the DataTables filter slot),
                         styled to match .programme-dt-search. Enter submits the form. --}}
                    <div class="ot-search">
                        <input type="text" name="search" id="otSearchInput" class="ot-search-input"
                            value="{{ $search }}" placeholder="Search name, OT code, email, cadre"
                            autocomplete="off" aria-label="Search officer trainees">
                        <button type="button" class="ot-search-clear" id="otSearchClear"
                            aria-label="Clear search" title="Clear search" {{ $search ? '' : 'hidden' }}>
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </form>

            <div class="programme-dt-panel">
                <div class="table-responsive">
                    {{-- data-sargam-dt-ui="false": Laravel paginates this page server-side and
                         the .programme-dt-footer below is hand-written. Without the opt-out the
                         global DataTables enhancer hijacks and empties that footer. --}}
                    <table id="otDirectoryTable"
                        class="table table-hover align-middle mb-0 w-100 programme-dt-table"
                        data-sargam-dt-ui="false">
                        <thead>
                            <tr>
                                {{-- Photo, name and OT code share one identity column.
                                     Nine separate columns overflowed the panel and clipped
                                     the right edge off. Both name and OT code still export
                                     as their own fields — see OT_EXPORT_COLUMN_KEYS. --}}
                                <th>S. No.</th>
                                <th>Name / OT Code</th>
                                <th>Room No.</th>
                                <th>Room Extension No.</th>
                                <th>Email ID</th>
                                <th>Course Name</th>
                                <th>Cadre Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                                @php
                                    // Already entity-decoded by the controller, so the export and
                                    // the screen show the same text — see decodeEntities().
                                    $courseFull = trim((string) $student->course_name);
                                    $courseShort = trim((string) $student->couse_short_name);
                                    $otName = $student->display_name ?: '-';
                                    $otCode = $student->generated_OT_code ?: '-';
                                    $otPhoto = !empty($student->photo_path)
                                        ? asset('storage/' . $student->photo_path)
                                        : null;
                                @endphp
                                <tr>
                                    <td>{{ ($students->firstItem() ?? 1) + $index }}</td>
                                    <td>
                                        <span class="ot-name-cell">
                                            {{-- Initials sit underneath; the photo is layered over
                                                 them and only revealed once it actually decodes, so
                                                 a 404 or a missing photo_path leaves the monogram
                                                 showing rather than a broken-image glyph. --}}
                                            <span class="ot-avatar" aria-hidden="true">
                                                @if($student->initials !== '')
                                                    <span class="ot-avatar-initials">{{ $student->initials }}</span>
                                                @else
                                                    <i class="bi bi-person ot-avatar-icon"></i>
                                                @endif
                                                @if($otPhoto)
                                                    <img src="{{ $otPhoto }}" alt="" class="ot-avatar-img"
                                                        loading="lazy" decoding="async">
                                                @endif
                                            </span>
                                            <span class="ot-name-lines">
                                                <span class="ot-name">{{ $otName }}</span>
                                                <span class="ot-code">{{ $otCode }}</span>
                                            </span>
                                        </span>
                                    </td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>{{ $student->email ?: '-' }}</td>
                                    <td>
                                        @if($courseFull !== '' || $courseShort !== '')
                                            <span class="ot-course-cell" title="{{ $courseFull ?: $courseShort }}">{{ $courseShort ?: $courseFull }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $student->cadre_name ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2"></i>
                                        <span class="fw-medium">
                                            @if($courses->isEmpty())
                                                No {{ $status === 'archive' ? 'archived' : 'active' }} programmes
                                            @elseif($search !== '')
                                                No officer trainees match &ldquo;{{ $search }}&rdquo;
                                            @else
                                                No officer trainees in this programme
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Footer variant B — Laravel paginates, so this is hand-written and reuses the
                 .dataTables_length / .dataTables_info class names for the shared styling. --}}
            <div class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3">
                <div class="programme-dt-pagination">
                    {{ $students->links('vendor.pagination.custom') }}
                </div>
                <div class="programme-dt-count d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <div class="dataTables_length">
                        <label class="mb-0">Showing
                            <select id="otPerPage" class="form-select form-select-sm" aria-label="Rows per page">
                                @foreach(['10' => '10', '25' => '25', '50' => '50', '100' => '100', '200' => '200', 'all' => 'All'] as $ppValue => $ppLabel)
                                    <option value="{{ $ppValue }}" {{ $perPage === $ppValue ? 'selected' : '' }}>{{ $ppLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="dataTables_info">of {{ number_format($students->total()) }} items</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Column visibility (colvis-item card grid — the programme-dt variant).
     #otColumnToggleGrid is registered in custom.css alongside the other grids. --}}
<div class="modal fade" id="otColumnVisibilityModal" tabindex="-1" aria-labelledby="otColumnVisibilityLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-2">
                <h5 class="modal-title fw-bold" id="otColumnVisibilityLabel">Column Visibility</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-0">
                <hr class="mt-0">
                <div class="row g-3" id="otColumnToggleGrid"></div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-primary rounded-3 px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Everything here is namespaced under .ot-page so it can't leak into the ~400
   other admin views. Values come from the --ds-* token layer (docs/design.md). */

.ot-page .ot-record-count {
    color: var(--ds-ink-muted, #667085);
    font-size: 0.9375rem;
    font-weight: 500;
}

/* Print + Download — same shape as the Attendance page's download control.
   Print is an <a>, Download a <button>, so the box model is set explicitly
   rather than relying on button defaults. */
.ot-page .ot-download-btn {
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

.ot-page .ot-download-btn:hover:not(:disabled):not(.disabled) {
    background: #f2f7fc;
    border-color: var(--ds-primary, #004a93);
    color: var(--ds-primary, #004a93);
}

.ot-page .ot-download-btn:disabled,
.ot-page .ot-download-btn.disabled {
    color: #98a2b3;
    opacity: 1;
    pointer-events: none;
}

.ot-page .ot-download-btn i {
    font-size: 1rem;
    line-height: 1;
}

/* Search — mirrors .programme-dt-search, but wraps a real <input> because this
   page filters on the server instead of through the DataTables filter slot. */
.ot-page .ot-search {
    position: relative;
    flex: 0 0 auto;
    width: 300px;
    max-width: 100%;
}

.ot-page .ot-search::before {
    content: "\F52A";
    font-family: "bootstrap-icons";
    position: absolute;
    left: 0.875rem;
    top: 50%;
    transform: translateY(-50%);
    color: #98a2b3;
    font-size: 1rem;
    pointer-events: none;
    z-index: 2;
}

.ot-page .ot-search-input {
    width: 100%;
    height: 40px;
    padding: 0.5rem 2.25rem 0.5rem 2.375rem;
    border: 1px solid #d0d5dd;
    border-radius: 8px;
    font-size: 0.9375rem;
    color: #344054;
    background: var(--ds-surface, #fff);
}

.ot-page .ot-search-input::placeholder {
    color: #98a2b3;
}

.ot-page .ot-search-input:focus {
    outline: 0;
    border-color: var(--ds-primary, #004a93);
    box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12);
}

.ot-page .ot-search-clear {
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    padding: 0.15rem;
    border: 0;
    background: transparent;
    color: #98a2b3;
    line-height: 1;
}

.ot-page .ot-search-clear:hover {
    color: #475467;
}

/* Identity cell = photo + name over OT code. The old markup used a
   .directory-photo class that had no size rule anywhere in the app, so photos
   rendered at their natural size. */
.ot-page .ot-name-cell {
    display: inline-flex;
    align-items: center;
    gap: var(--ds-space-2, 0.5rem);
}

/* Avatar = initials underneath, photo layered on top. The photo starts
   transparent and is only revealed by .is-loaded, so a broken or missing image
   never paints over the monogram. */
.ot-page .ot-avatar {
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

.ot-page .ot-avatar-initials {
    font-size: 0.8125rem;
    font-weight: 600;
    line-height: 1;
    letter-spacing: 0.01em;
    text-transform: uppercase;
}

.ot-page .ot-avatar-icon {
    font-size: 1.125rem;
    line-height: 1;
    opacity: 0.7;
}

.ot-page .ot-avatar-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0;
    transition: opacity 0.15s ease;
}

.ot-page .ot-avatar-img.is-loaded {
    opacity: 1;
}

@media (prefers-reduced-motion: reduce) {
    .ot-page .ot-avatar-img {
        transition: none;
    }
}

.ot-page .ot-name-lines {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
    min-width: 0;
}

.ot-page .ot-name {
    color: #101828;
    font-weight: 500;
}

.ot-page .ot-code {
    color: var(--ds-ink-muted, #667085);
    font-size: 0.8125rem;
}

/* Column 2 is the identity cell. The shared .programme-dt-table rule sets it to
   wrap at 420px, which breaks two-word names across lines when space is tight. */
.ot-page .programme-dt-table tbody td:nth-child(2) {
    max-width: none;
    white-space: nowrap;
}

/* Course Name: short label in the cell, full name on hover. Without the clamp a
   single 100-char programme title wraps every row to ~10 lines. */
.ot-page .ot-course-cell {
    display: inline-block;
    max-width: 220px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: middle;
    cursor: help;
    text-decoration: underline dotted;
    text-underline-offset: 0.15em;
}

@media (max-width: 991.98px) {
    .ot-page .ot-search {
        width: 100%;
    }
}

@media (max-width: 767.98px) {
    .ot-page .ot-avatar {
        width: 32px;
        height: 32px;
    }

    .ot-page .ot-avatar-initials {
        font-size: 0.6875rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    'use strict';

    var form = document.getElementById('otFilterForm');
    var table = document.getElementById('otDirectoryTable');
    if (!form || !table) return;

    // ── Filters ──────────────────────────────────────────────────────────────
    var courseSelect = document.getElementById('otCourseSelect');
    var searchInput = document.getElementById('otSearchInput');
    var searchClear = document.getElementById('otSearchClear');

    // A GET submit rebuilds the query string from the form's own fields, so ?page=
    // is dropped and every filter change lands back on page 1 — which is what we
    // want, since page N of the previous result set is meaningless.
    function submitFiltered() {
        form.submit();
    }

    if (courseSelect) {
        courseSelect.addEventListener('change', submitFiltered);
    }

    if (searchInput) {
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitFiltered();
            }
        });
        searchInput.addEventListener('input', function () {
            if (searchClear) searchClear.hidden = this.value === '';
        });
    }

    if (searchClear) {
        searchClear.addEventListener('click', function () {
            searchInput.value = '';
            this.hidden = true;
            submitFiltered();
        });
    }

    // Rows per page: a plain URL jump, so it composes with whatever filters and
    // sort are already in the query string.
    var perPage = document.getElementById('otPerPage');
    if (perPage) {
        perPage.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('per_page', this.value);
            url.searchParams.delete('page');
            window.location.href = url.toString();
        });
    }

    // ── Avatars ──────────────────────────────────────────────────────────────
    // Reveal a photo only once it has actually decoded; drop it on error so the
    // initials underneath stay visible. load/error don't bubble, hence capture.
    function settleAvatar(img) {
        if (img.naturalWidth > 0) {
            img.classList.add('is-loaded');
        } else {
            img.remove();
        }
    }

    table.addEventListener('load', function (e) {
        if (e.target.classList && e.target.classList.contains('ot-avatar-img')) settleAvatar(e.target);
    }, true);

    table.addEventListener('error', function (e) {
        if (e.target.classList && e.target.classList.contains('ot-avatar-img')) settleAvatar(e.target);
    }, true);

    // This script runs after the table has painted, so images that already
    // finished (cached hits, and any that failed) never fire an event for us.
    table.querySelectorAll('.ot-avatar-img').forEach(function (img) {
        if (img.complete) settleAvatar(img);
    });

    // ── Column visibility ────────────────────────────────────────────────────
    // Header index -> the export column key(s) that column stands for, matching
    // DirectoryController::OT_EXPORT_COLUMNS. Index 1 carries two: the identity
    // column merges name + OT code on screen, but they stay separate fields in
    // the report. Positional — adding a <th> means editing this array and the
    // empty-state colspan.
    var OT_EXPORT_COLUMN_KEYS = [
        ['sno'], ['name', 'ot_code'], ['room_no'], ['room_ext'], ['email'], ['course'], ['cadre']
    ];
    var OT_EXPORT_COL_COUNT = OT_EXPORT_COLUMN_KEYS.reduce(function (n, keys) { return n + keys.length; }, 0);
    // Print lives outside the dropdown but still tracks the visible columns.
    var OT_DOWNLOAD_LINK_IDS = @json(array_merge([$otPrintLinkId], array_column($otDownloads, 'id')));

    // Keyed by user so two people sharing a browser don't inherit each other's
    // hidden columns. Stores LABELS, not indices — a column inserted mid-table
    // would silently shift every saved index (see docs/column-visibility.md).
    var STORAGE_KEY = 'sargam.otDirectory.hiddenCols.{{ auth()->id() ?? 'guest' }}';

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

    var headers = Array.prototype.slice.call(table.querySelectorAll('thead th'));
    var labels = headers.map(function (th) {
        return th.textContent.replace(/\s+/g, ' ').trim();
    });

    function setColumnVisible(index, visible) {
        var nth = index + 1;
        table.querySelectorAll('tr').forEach(function (row) {
            // The empty-state row spans every column; hiding a cell in it would
            // wipe the whole message.
            if (row.querySelector('[colspan]')) return;
            var cell = row.children[nth - 1];
            if (cell) cell.style.display = visible ? '' : 'none';
        });
    }

    function applyHidden(hidden) {
        labels.forEach(function (label, i) {
            if (!label) return;
            setColumnVisible(i, hidden.indexOf(label) === -1);
        });
    }

    function syncDownloadLinks(hidden) {
        var keys = [];
        labels.forEach(function (label, i) {
            if (hidden.indexOf(label) !== -1) return;
            (OT_EXPORT_COLUMN_KEYS[i] || []).forEach(function (key) { keys.push(key); });
        });

        // Every format follows what's on screen, so Print and the PDF show the
        // same columns the user left visible.
        OT_DOWNLOAD_LINK_IDS.forEach(function (id) {
            var link = document.getElementById(id);
            if (!link) return;
            var url = new URL(link.href, window.location.origin);
            // Omit ?cols= entirely while nothing is hidden — the server reads
            // "no cols" as "every column".
            if (keys.length && keys.length !== OT_EXPORT_COL_COUNT) {
                url.searchParams.set('cols', keys.join(','));
            } else {
                url.searchParams.delete('cols');
            }
            link.href = url.toString();
        });
    }

    var grid = document.getElementById('otColumnToggleGrid');

    function buildGrid() {
        if (!grid) return;
        var hidden = readHidden();
        grid.innerHTML = '';

        labels.forEach(function (label, i) {
            if (!label) return;

            var inputId = 'otColvis_' + i;
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
                setColumnVisible(i, this.checked);
                syncDownloadLinks(current);
            });

            var text = document.createElement('span');
            text.textContent = label; // .textContent, not innerHTML — labels are DOM text

            wrap.appendChild(cb);
            wrap.appendChild(text);
            cell.appendChild(wrap);
            grid.appendChild(cell);
        });
    }

    var hiddenOnLoad = readHidden();
    applyHidden(hiddenOnLoad);       // before paint, so hidden columns don't flash back
    syncDownloadLinks(hiddenOnLoad);
    buildGrid();
})();
</script>
@endpush

@endsection
