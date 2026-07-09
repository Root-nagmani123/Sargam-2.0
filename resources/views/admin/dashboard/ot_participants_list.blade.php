@extends('admin.layouts.master')

@section('title', 'OT/ Participants List')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
<style>
    .ot-list-page .sl-filter-select {
        width: 200px; flex: 0 0 auto; height: 40px; border-radius: 8px;
        font-size: 0.9375rem; color: #344054;
        padding: 0.5rem 2.25rem 0.5rem 0.875rem; background-position: right 0.75rem center;
    }
    .ot-list-page .sl-filter-item { display: inline-flex; align-items: center; }
    .ot-list-page .sl-filter-select:focus { border-color: #004a93; box-shadow: 0 0 0 3px rgba(0, 74, 147, 0.12); }
    .ot-list-page .sl-daterange-wrap { position: relative; }
    .ot-list-page .sl-daterange-input {
        width: 220px; height: 40px; padding: 1.05rem 0.875rem 0.15rem; cursor: pointer;
        background-image: none; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.85rem;
    }
    .ot-list-page .sl-daterange-label { position: absolute; top: 4px; left: 0.875rem; font-size: 0.68rem; color: #667085; pointer-events: none; }

    .ot-list-page .sl-toolbar-btn {
        height: 40px; display: inline-flex; align-items: center; gap: 0.5rem; padding: 0 1.1rem;
        font-size: 0.9375rem; font-weight: 500; color: #004a93; border: 1px solid #d0d5dd; border-radius: 8px; background: #fff;
    }
    .ot-list-page .sl-toolbar-btn:hover { background: #f9fafb; }
    .ot-list-page .sl-toolbar-btn i { font-size: 1rem; line-height: 1; }

    .ot-list-page .sl-table-shell { position: relative; }
    .ot-list-page .sl-table-loading {
        position: absolute; inset: 0; background: rgba(255, 255, 255, 0.74);
        display: none; align-items: center; justify-content: center; z-index: 30; backdrop-filter: blur(1px);
    }
    .ot-list-page .sl-table-loading.is-active { display: flex; }
    .ot-list-page .sl-table-loading-card {
        display: inline-flex; align-items: center; gap: 0.65rem; padding: 0.7rem 0.95rem;
        border: 1px solid #d0d5dd; border-radius: 10px; background: #fff;
        box-shadow: 0 8px 22px rgba(16, 24, 40, 0.12); color: #344054; font-weight: 600; font-size: 0.9rem;
    }

    .ot-list-page .programme-dt-table tbody td a.sl-count,
    .ot-list-page .programme-dt-table tbody td span.sl-count {
        color: #004a93; font-weight: 600; text-decoration: none; white-space: wrap;
    }
    .ot-list-page .programme-dt-table tbody td a.sl-count:hover { text-decoration: underline; cursor: pointer; }
    .ot-list-page .programme-dt-table tbody td span.sl-count:hover { text-decoration: underline; cursor: default; }

    .ot-list-page .programme-dt-table { width: 100% !important; }
    .ot-list-page .programme-dt-table th,
    .ot-list-page .programme-dt-table td { white-space: nowrap; }
    .ot-list-page .programme-dt-table th:nth-child(1), .ot-list-page .programme-dt-table td:nth-child(1) { min-width: 70px; }
    .ot-list-page .programme-dt-table th:nth-child(2), .ot-list-page .programme-dt-table td:nth-child(2) { min-width: 120px; }
    .ot-list-page .programme-dt-table th:nth-child(3), .ot-list-page .programme-dt-table td:nth-child(3) { min-width: 200px; }

    .ot-list-page .sl-dt-scroll-host { overflow-x: auto; overflow-y: visible; }
    .ot-list-page .programme-dt-table { overflow: visible !important; }
    .ot-list-page .programme-dt-table { --sl-pin-left-0: 0px; --sl-pin-left-1: 70px; --sl-pin-left-2: 190px; }
    .ot-list-page .programme-dt-table thead th:nth-child(-n+3),
    .ot-list-page .programme-dt-table tbody td:nth-child(-n+3) { position: sticky; }
    .ot-list-page .programme-dt-table thead th:nth-child(-n+3) { z-index: 6; background: #f2f4f7 !important; }
    .ot-list-page .programme-dt-table tbody td:nth-child(-n+3) { z-index: 3; background: #fff; }
    .ot-list-page .programme-dt-table tbody tr:hover td:nth-child(-n+3) { background: #f7fafc; }
    .ot-list-page .programme-dt-table th:nth-child(1), .ot-list-page .programme-dt-table td:nth-child(1) { left: var(--sl-pin-left-0); }
    .ot-list-page .programme-dt-table th:nth-child(2), .ot-list-page .programme-dt-table td:nth-child(2) { left: var(--sl-pin-left-1); }
    .ot-list-page .programme-dt-table th:nth-child(3), .ot-list-page .programme-dt-table td:nth-child(3) { left: var(--sl-pin-left-2); }
    .ot-list-page .programme-dt-table th:nth-child(3), .ot-list-page .programme-dt-table td:nth-child(3) { box-shadow: 1px 0 0 #e5e7eb; }
</style>
@endpush

@section('content')
@php
    $filters = $filters ?? [];
    $activeStatus = ($filters['status'] ?? 'active') === 'archive' ? 'archive' : 'active';
@endphp
<div class="container-fluid ot-list-page">
    <x-breadcrum title="OT/ Participants List" :showBack="true" :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Academics'],
        ['label' => 'Time Table'],
        ['label' => 'OT/ Participants List'],
    ]" />
    <x-session_message />

    {{-- Active / Archived tabs + Print --}}
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
        <ul class="nav nav-pills gap-2 p-1 rounded-1 programme-status-tabs bg-white" role="group" aria-label="Course status">
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $activeStatus === 'active' ? 'active' : '' }}"
                    data-status="active">Active</button>
            </li>
            <li class="nav-item" role="presentation">
                <button type="button" class="nav-link rounded-1 px-4 py-2 fw-semibold programme-status-pill {{ $activeStatus === 'archive' ? 'active' : '' }}"
                    data-status="archive">Archived</button>
            </li>
        </ul>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" class="btn sl-toolbar-btn border-0" id="otListPrintBtn" >
                <i class="bi bi-printer" aria-hidden="true"></i>
                <span>Print</span>
            </button>
        </div>
    </div>

    <div class="card border-0 shadow-sm overflow-hidden rounded-3">
        <div class="card-body p-3 p-md-4">

            <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3 mb-3 programme-dt-toolbar">
                <div class="d-flex flex-wrap align-items-center gap-3">
                    <span class="programme-dt-filters-label">Filters</span>

                    @if(($courseOptions ?? collect())->isNotEmpty())
                    <div class="sl-filter-item">
                        <select id="courseFilter" class="form-select sl-filter-select" aria-label="Filter by course">
                            <option value="">Course Name</option>
                            @foreach($courseOptions as $course)
                                @php
                                    $cStart = !empty($course->start_year) ? \Carbon\Carbon::parse($course->start_year)->format('j F Y') : '';
                                    $cEnd = !empty($course->end_date) ? \Carbon\Carbon::parse($course->end_date)->format('j F Y') : '';
                                    $cDuration = ($cStart && $cEnd) ? $cStart . ' to ' . $cEnd : '';
                                @endphp
                                <option value="{{ $course->pk }}"
                                    data-shortname="{{ $course->couse_short_name ?? '' }}"
                                    data-duration="{{ $cDuration }}"
                                    {{ (string)($filters['course_id'] ?? '') === (string)$course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    @if(($cadreOptions ?? collect())->isNotEmpty())
                    <div class="sl-filter-item">
                        <select id="cadreFilter" class="form-select sl-filter-select" aria-label="Filter by cadre">
                            <option value="">Cadre</option>
                            @foreach($cadreOptions as $cadre)
                                <option value="{{ $cadre }}" {{ (string)($filters['cadre'] ?? '') === (string)$cadre ? 'selected' : '' }}>{{ $cadre }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="sl-filter-item">
                        <div class="sl-daterange-wrap">
                            <span class="sl-daterange-label">Time Period</span>
                            <input type="text" id="timePeriodFilter" class="form-control sl-filter-select sl-daterange-input"
                                placeholder="Select dates" autocomplete="off" readonly aria-label="Filter by time period"
                                value="{{ (!empty($filters['from_date']) && !empty($filters['to_date'])) ? \Carbon\Carbon::parse($filters['from_date'])->format('d/m/Y').' - '.\Carbon\Carbon::parse($filters['to_date'])->format('d/m/Y') : '' }}">
                        </div>
                    </div>
                    <button type="button" class="btn programme-dt-btn-reset" id="resetFilters">Reset Filters</button>
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2 ms-lg-auto">
                    <button type="button" class="btn programme-dt-btn-columns sl-toolbar-btn" id="btnOtColumns" data-bs-toggle="modal" data-bs-target="#otColumnVisibilityModal" title="Show / hide columns">
                        <span>Columns</span>
                        <i class="bi bi-layout-three-columns" aria-hidden="true"></i>
                    </button>
                    <div class="sl-filter-item">
                        <div id="otDtSearch" class="programme-dt-search" data-dt-search-for="otParticipantsTable"></div>
                    </div>
                </div>
            </div>

            <div class="sl-table-shell">
                <div class="sl-table-loading" id="otTableLoading" aria-live="polite" aria-busy="false">
                    <div class="sl-table-loading-card">
                        <span class="spinner-border spinner-border-sm text-primary" role="status" aria-hidden="true"></span>
                        <span>Loading participants...</span>
                    </div>
                </div>

                <div class="programme-dt-panel">
                    <div class="sl-dt-scroll-host">
                        <table class="table programme-dt-table" id="otParticipantsTable">
                            <thead>
                                <tr>
                                    <th>S. No.</th>
                                    <th>OT Code</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Cadre</th>
                                    <th>House Name</th>
                                    <th>Total Duty (Count)</th>
                                    <th>Duty Type</th>
                                    <th>Total Medical Exemption Count</th>
                                    <th>Total PT Exemption (Days)</th>
                                    <th>Total Stationed Leave (Days)</th>
                                    <th>Total Notice/Memo</th>
                                    <th>Total Discipline Memo</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div id="otDtFooter" class="programme-dt-footer d-flex flex-wrap align-items-center justify-content-between gap-3" data-dt-footer-for="otParticipantsTable"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Column Visibility Modal -->
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

@push('scripts')
@php
    // Official letterhead logos embedded as base64 so the print window (a blank
    // about:blank document) can render them without extra network requests.
    $__printAsset = function (array $candidates): string {
        foreach ($candidates as $rel) {
            $path = public_path($rel);
            if (! is_file($path) || ! is_readable($path)) { continue; }
            $raw = @file_get_contents($path);
            if ($raw === false) { continue; }
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime = $ext === 'svg' ? 'image/svg+xml' : (in_array($ext, ['jpg', 'jpeg']) ? 'image/jpeg' : 'image/png');
            return 'data:' . $mime . ';base64,' . base64_encode($raw);
        }
        return '';
    };
    $printLogoLeft  = $__printAsset(['admin_assets/images/logos/logo_new.png', 'images/lbsnaa_logo.jpg']);
    $printLogoRight = $__printAsset(['admin_assets/images/logos/constitution-75.png', 'admin_assets/images/logos/Azadi-Ka-Amrit-Mahotsav-Logo.png', 'images/azadi.png']);
@endphp
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        const PRINT_LOGO_LEFT = @json($printLogoLeft);
        const PRINT_LOGO_RIGHT = @json($printLogoRight);
        const ACADEMY_HI = 'लाल बहादुर शास्त्री राष्ट्रीय प्रशासन अकादमी, मसूरी';
        const ACADEMY_EN = 'Lal Bahadur Shastri National Academy of Administration, Mussoorie';
        const filters = @json($filters ?? []);
        const baseUrl = "{{ route('admin.dashboard.ot-participants') }}";
        const LOCKED_COLUMNS = [0, 1, 2]; // S.No, OT Code, Name — frozen & always visible
        const DUTY_TYPE_COL = 7; // "Duty Type" — only meaningful for a single-day filter
        let dt = null;

        // Duty Type is per-day, so it only makes sense when the Time Period is a single
        // day (from == to). For a multi-day range (or no date), it is hidden.
        function isSingleDayFilter() {
            const f = (filters.from_date || '').toString();
            const t = (filters.to_date || '').toString();
            return f !== '' && f === t;
        }
        let currentStatus = (filters.status === 'archive') ? 'archive' : 'active';
        let loadingRequests = 0;

        function setTableLoading(show) {
            const $loader = $('#otTableLoading');
            if (!$loader.length) { return; }
            loadingRequests = show ? loadingRequests + 1 : Math.max(loadingRequests - 1, 0);
            const active = loadingRequests > 0;
            $loader.toggleClass('is-active', active).attr('aria-busy', active ? 'true' : 'false');
        }

        function getFilterState() {
            return {
                status: currentStatus,
                course_id: $('#courseFilter').val() || '',
                cadre: $('#cadreFilter').val() || '',
                session: $('#sessionFilter').val() || '',
                participant: $('#participantFilter').val() || '',
                from_date: (filters.from_date || '').toString(),
                to_date: (filters.to_date || '').toString(),
            };
        }

        function syncUrl() {
            const p = new URLSearchParams(window.location.search);
            const state = getFilterState();
            Object.entries(state).forEach(([k, v]) => {
                if (v === '' || v === null || v === undefined) { p.delete(k); } else { p.set(k, v); }
            });
            const qs = p.toString();
            window.history.replaceState(null, '', window.location.pathname + (qs ? '?' + qs : ''));
        }

        function applyFilter(changes) {
            Object.entries(changes || {}).forEach(([k, v]) => {
                if (k === 'from_date' || k === 'to_date') { filters[k] = v || ''; }
            });
            syncUrl();
            if (dt) { dt.ajax.reload(null, true); }
        }

        function relayoutPinnedColumns() {
            if (!dt) { return; }
            const tableNode = dt.table().node();
            if (!tableNode) { return; }
            const $headCells = $(tableNode).find('thead th');
            if (!$headCells.length) { return; }
            const fallbackWidths = [70, 120, 200];
            let runningLeft = 0;
            LOCKED_COLUMNS.forEach(function(colIdx, i) {
                const $cell = $headCells.eq(colIdx);
                const width = Math.max(0, Math.round(($cell.outerWidth() || fallbackWidths[i] || 100)));
                tableNode.style.setProperty('--sl-pin-left-' + i, runningLeft + 'px');
                runningLeft += width;
            });
        }

        dt = $('#otParticipantsTable').DataTable({
            processing: true,
            serverSide: true,
            deferRender: true,
            searchDelay: 400,
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, 200], [10, 25, 50, 100, 200]],
            order: [[0, 'asc']],
            language: { emptyTable: 'Data not found.' },
            responsive: false,
            autoWidth: false,
            ajax: {
                url: baseUrl,
                type: 'GET',
                data: function(d) {
                    Object.assign(d, getFilterState());
                }
            },
            columns: [
                { data: 's_no', name: 's_no', orderable: false },
                { data: 'ot_code', name: 'ot_code' },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'cadre', name: 'cadre' },
                { data: 'house', name: 'house' },
                { data: 'duty_count', name: 'duty_count', orderable: false, searchable: false },
                { data: 'duty_type', name: 'duty_type', orderable: false, searchable: false },
                { data: 'medical', name: 'medical', orderable: false, searchable: false },
                { data: 'pt', name: 'pt', orderable: false, searchable: false },
                { data: 'stationed', name: 'stationed', orderable: false, searchable: false },
                { data: 'notice_memo', name: 'notice_memo', orderable: false, searchable: false },
                { data: 'discipline_memo', name: 'discipline_memo', orderable: false, searchable: false },
            ]
        });

        dt.on('preXhr.dt', function() { setTableLoading(true); })
          .on('xhr.dt', function() { setTableLoading(false); })
          .on('error.dt', function() { setTableLoading(false); })
          .on('draw.dt column-visibility.dt', function() { relayoutPinnedColumns(); });

        /* ── Active / Archived tabs (full reload — course options + data are status-specific) ── */
        $('.programme-status-tabs .programme-status-pill[data-status]').on('click', function() {
            const st = $(this).data('status');
            if (st === currentStatus) { return; }
            const p = new URLSearchParams(window.location.search);
            p.set('status', st);
            p.delete('course_id'); // the course list differs per status
            window.location.href = baseUrl + (p.toString() ? '?' + p.toString() : '');
        });

        /* ── Filters ── */
        $('#courseFilter').on('change', function() { applyFilter({ course_id: this.value }); });
        $('#cadreFilter').on('change', function() { applyFilter({ cadre: this.value }); });
        $('#sessionFilter').on('change', function() { applyFilter({ session: this.value }); });
        $('#participantFilter').on('change', function() { applyFilter({ participant: this.value }); });
        $('#resetFilters').on('click', function() { window.location.href = baseUrl; });

        /* ── Time Period date-range ── */
        const $period = $('#timePeriodFilter');
        $period.daterangepicker({
            autoUpdateInput: false, opens: 'left',
            locale: { format: 'DD-MM-YYYY', cancelLabel: 'Clear', applyLabel: 'Apply' },
            ranges: {
                'Today': [moment(), moment()],
                'This Month': [moment().startOf('month'), moment().endOf('month')],
                'Last 3 Months': [moment().subtract(3, 'months').startOf('day'), moment()],
                'This Year': [moment().startOf('year'), moment().endOf('year')],
            },
        });
        $period.on('apply.daterangepicker', function(ev, picker) {
            $period.val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
            applyFilter({ from_date: picker.startDate.format('YYYY-MM-DD'), to_date: picker.endDate.format('YYYY-MM-DD') });
            setupOtColumns(); // re-evaluate Duty Type column availability for the new range
        });
        $period.on('cancel.daterangepicker', function() { $period.val(''); applyFilter({ from_date: '', to_date: '' }); setupOtColumns(); });

        /* ── Print (table data only, all filtered rows, clean header) ── */
        // Column titles + data keys, in table order. Only currently-visible columns
        // are printed so the printout matches what's on screen.
        // `w` = relative width weight used to size columns in the fixed-layout print
        // table so text columns get room and numeric columns stay compact.
        const PRINT_COLUMNS = [
            { title: 'S. No.', data: 's_no', w: 3 },
            { title: 'OT Code', data: 'ot_code', w: 6 },
            { title: 'Name', data: 'name', w: 12 },
            { title: 'Email', data: 'email', w: 14 },
            { title: 'Cadre', data: 'cadre', w: 8 },
            { title: 'House Name', data: 'house', w: 8 },
            { title: 'Total Duty (Count)', data: 'duty_count', w: 6 },
            { title: 'Duty Type', data: 'duty_type', w: 8 },
            { title: 'Total Medical Exemption Count', data: 'medical', w: 7 },
            { title: 'Total PT Exemption (Days)', data: 'pt', w: 7 },
            { title: 'Total Stationed Leave (Days)', data: 'stationed', w: 7 },
            { title: 'Total Notice/Memo', data: 'notice_memo', w: 6 },
            { title: 'Total Discipline Memo', data: 'discipline_memo', w: 6 },
        ];

        function stripHtml(html) {
            const tmp = document.createElement('div');
            tmp.innerHTML = html == null ? '' : String(html);
            return (tmp.textContent || tmp.innerText || '').replace(/\s+/g, ' ').trim();
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text == null ? '' : String(text);
            return div.innerHTML;
        }

        function buildLetterheadHtml() {
            const $sel = $('#courseFilter option:selected');
            const hasCourse = !!$('#courseFilter').val();
            const courseName = hasCourse ? $sel.text().trim() : '';
            const shortName = hasCourse ? ($sel.attr('data-shortname') || '') : '';
            const duration = hasCourse ? ($sel.attr('data-duration') || '') : '';
            const courseTitle = courseName + (shortName ? ' (' + shortName + ')' : '');

            const leftImg = PRINT_LOGO_LEFT ? '<img class="lh-logo" src="' + PRINT_LOGO_LEFT + '" alt="">' : '';
            const rightImg = PRINT_LOGO_RIGHT ? '<img class="lh-logo" src="' + PRINT_LOGO_RIGHT + '" alt="">' : '';

            return '' +
                '<table class="lh"><tr>' +
                '<td class="lh-side">' + leftImg + '</td>' +
                '<td class="lh-center">' +
                    '<div class="lh-hi">' + escapeHtml(ACADEMY_HI) + '</div>' +
                    '<div class="lh-en">' + escapeHtml(ACADEMY_EN) + '</div>' +
                    (courseTitle ? '<div class="lh-course">' + escapeHtml(courseTitle) + '</div>' : '') +
                    (duration ? '<div class="lh-dates">(' + escapeHtml(duration) + ')</div>' : '') +
                '</td>' +
                '<td class="lh-side">' + rightImg + '</td>' +
                '</tr></table>' +
                '<hr class="lh-rule">' +
                '<h2 class="doc-title">OT / Participants List</h2>';
        }

        function buildFilterSummary() {
            const parts = [];
            const course = $('#courseFilter option:selected').text().trim();
            if ($('#courseFilter').val()) { parts.push('Course: ' + course); }
            const cadre = $('#cadreFilter').val();
            if (cadre) { parts.push('Cadre: ' + cadre); }
            const period = $('#timePeriodFilter').val().trim();
            if (period) { parts.push('Time Period: ' + period); }
            parts.push('Status: ' + (currentStatus === 'archive' ? 'Archived' : 'Active'));
            return parts.join(' &nbsp;|&nbsp; ');
        }

        function renderPrint(win, rows) {
            if (!win) { return; }

            // Respect on-screen column visibility.
            const visibleCols = PRINT_COLUMNS.filter(function(col, idx) {
                try { return dt.column(idx).visible(); } catch (e) { return true; }
            });

            const totalWeight = visibleCols.reduce(function(sum, c) { return sum + (c.w || 6); }, 0) || 1;
            const colGroupHtml = '<colgroup>' + visibleCols.map(function(c) {
                const pct = ((c.w || 6) / totalWeight * 100).toFixed(2);
                return '<col style="width:' + pct + '%">';
            }).join('') + '</colgroup>';

            const headHtml = visibleCols.map(function(c) { return '<th>' + escapeHtml(c.title) + '</th>'; }).join('');

            let bodyHtml = '';
            if (!rows.length) {
                bodyHtml = '<tr><td colspan="' + visibleCols.length + '" style="text-align:center">No data found.</td></tr>';
            } else {
                bodyHtml = rows.map(function(row) {
                    const cells = visibleCols.map(function(c) {
                        return '<td>' + escapeHtml(stripHtml(row[c.data])) + '</td>';
                    }).join('');
                    return '<tr>' + cells + '</tr>';
                }).join('');
            }

            const html =
                '<html><head><title>OT / Participants List</title><style>' +
                '@page{size:A4 landscape;margin:1cm;}' +
                'body{font-family:Arial,sans-serif;margin:20px;color:#101828;}' +
                '*{box-sizing:border-box;}' +
                // ── Official letterhead ──
                '.lh{width:100%;border-collapse:collapse;margin-bottom:4px;}' +
                '.lh td{vertical-align:middle;}' +
                '.lh-side{width:90px;text-align:center;}' +
                '.lh-logo{max-height:72px;max-width:88px;}' +
                '.lh-center{text-align:center;padding:0 8px;}' +
                '.lh-hi{font-size:15px;font-weight:bold;color:#1a1a1a;line-height:1.3;}' +
                '.lh-en{font-size:13px;font-weight:bold;color:#102a43;margin-top:2px;line-height:1.3;}' +
                '.lh-course{font-size:12px;font-weight:bold;color:#243b53;margin-top:5px;}' +
                '.lh-dates{font-size:11px;color:#486581;margin-top:1px;}' +
                '.lh-rule{border:0;border-top:2px solid #004a93;margin:6px 0 2px;}' +
                '.doc-title{text-align:center;color:#004a93;margin:8px 0 4px;font-size:20px;}' +
                '.meta{font-size:12px;color:#475467;margin:0 0 14px;text-align:center;}' +
                'table.data-tbl{width:100%;border-collapse:collapse;margin-top:8px;font-size:9px;table-layout:fixed;}' +
                'table.data-tbl th,table.data-tbl td{border:1px solid #ddd;padding:4px 5px;text-align:left;vertical-align:top;word-break:break-word;overflow-wrap:anywhere;white-space:normal;}' +
                'table.data-tbl th{background-color:#004a93;color:#fff;font-weight:bold;}' +
                'table.data-tbl tbody tr:nth-child(even){background-color:#f7f7f7;}' +
                'table.data-tbl tr{page-break-inside:avoid;}' +
                'table.data-tbl thead{display:table-header-group;}' +
                '@media print{body{margin:0;}}' +
                '</style></head><body>' +
                buildLetterheadHtml() +
                '<p class="meta">' + buildFilterSummary() + '<br>Total Records: ' + rows.length +
                ' &nbsp;|&nbsp; Print Date: ' + escapeHtml(new Date().toLocaleString()) + '</p>' +
                '<table class="data-tbl">' + colGroupHtml + '<thead><tr>' + headHtml + '</tr></thead><tbody>' + bodyHtml + '</tbody></table>' +
                '</body></html>';

            win.document.open();
            win.document.write(html);
            win.document.close();
            win.focus();
            setTimeout(function() { win.print(); }, 300);
        }

        $('#otListPrintBtn').on('click', function() {
            // Open the window synchronously (inside the click) so pop-up blockers allow it.
            const win = window.open('', '_blank');
            if (win) {
                win.document.write('<p style="font-family:Arial;padding:20px">Preparing print&hellip;</p>');
            }
            // Pull ALL filtered rows (length = -1), not just the current page.
            const params = Object.assign({}, getFilterState(), { start: 0, length: -1, draw: 1 });
            setTableLoading(true);
            $.ajax({ url: baseUrl, type: 'GET', data: params })
                .done(function(res) {
                    setTableLoading(false);
                    renderPrint(win, (res && res.data) ? res.data : []);
                })
                .fail(function() {
                    setTableLoading(false);
                    if (win) { win.close(); }
                    alert('Unable to load data for printing. Please try again.');
                });
        });

        /* ── Dynamic columns: show / hide ── */
        const otColStorageKey = 'otParticipantsGrid:hiddenColumns:v1';
        function otGetHiddenCols() {
            try { const raw = localStorage.getItem(otColStorageKey); const arr = raw ? JSON.parse(raw) : []; return Array.isArray(arr) ? arr : []; }
            catch (e) { return []; }
        }
        function otPersistHiddenCols(arr) { try { localStorage.setItem(otColStorageKey, JSON.stringify(arr)); } catch (e) {} }
        function setupOtColumns() {
            if (!dt) { return; }
            const singleDay = isSingleDayFilter();
            const hidden = otGetHiddenCols().filter(idx => LOCKED_COLUMNS.indexOf(idx) === -1);
            otPersistHiddenCols(hidden);
            dt.columns().every(function() {
                const idx = this.index();
                if (LOCKED_COLUMNS.indexOf(idx) !== -1) { this.visible(true, false); return; }
                // Duty Type is forced hidden unless a single day is filtered.
                if (idx === DUTY_TYPE_COL) { this.visible(singleDay && hidden.indexOf(idx) === -1, false); return; }
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();
            relayoutPinnedColumns();
            const $grid = $('#otColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();
            dt.columns().every(function() {
                const idx = this.index();
                const title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                const isLocked = LOCKED_COLUMNS.indexOf(idx) !== -1;
                // Duty Type toggle is disabled (and unchecked) unless a single day is filtered.
                const dutyLocked = (idx === DUTY_TYPE_COL) && !singleDay;
                const inputId = 'otcolvis_' + idx;
                const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
                if (dutyLocked) { $label.attr('title', 'Available only when a single day is selected in Time Period'); }
                const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', isLocked || (!dutyLocked && hidden.indexOf(idx) === -1))
                    .prop('disabled', isLocked || dutyLocked);
                $cb.on('change', function() {
                    if (isLocked || dutyLocked) { return; }
                    const h = otGetHiddenCols();
                    const pos = h.indexOf(idx);
                    if (this.checked) { if (pos !== -1) h.splice(pos, 1); } else { if (pos === -1) h.push(idx); }
                    otPersistHiddenCols(h);
                    dt.column(idx).visible(this.checked, false);
                    dt.columns.adjust();
                    relayoutPinnedColumns();
                });
                $label.append($cb).append($('<span></span>').text(title));
                $cell.append($label);
                $grid.append($cell);
            });
        }
        setupOtColumns();

        $(window).on('resize', function() {
            if (dt) { dt.columns.adjust(); relayoutPinnedColumns(); }
        });
    });
</script>
@endpush

@endsection
