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
    .ot-list-page .programme-dt-table tbody td a.sl-count:hover,
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
    $tabCounts = $tabCounts ?? ['present' => 0, 'absent' => 0];
    $activeAtt = ($filters['attendance'] ?? 'present') === 'absent' ? 'absent' : 'present';
    $activeStatus = ($filters['status'] ?? 'active') === 'archive' ? 'archive' : 'active';
    $pad = fn ($n) => str_pad((string) (int) $n, 2, '0', STR_PAD_LEFT);
@endphp
<div class="container-fluid ot-list-page">
    <x-breadcrum title="OT/ Participants List" :showBack="true" :items="[
        ['label' => 'Home', 'url' => route('admin.dashboard')],
        ['label' => 'Academics'],
        ['label' => 'Time Table'],
        ['label' => 'OT/ Participants List'],
    ]" />
    <x-session_message />

    {{-- Present / Absent tabs + Print --}}
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
                                <option value="{{ $course->pk }}" {{ (string)($filters['course_id'] ?? '') === (string)$course->pk ? 'selected' : '' }}>{{ $course->course_name }}</option>
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

                    <div class="sl-filter-item">
                        <select id="participantFilter" class="form-select sl-filter-select" aria-label="Filter by OT / participant">
                            <option value="">OT / Participant</option>
                            @foreach(($participantOptions ?? []) as $p)
                                <option value="{{ $p->pk }}" {{ (string)($filters['participant'] ?? '') === (string)$p->pk ? 'selected' : '' }}>{{ $p->label }}</option>
                            @endforeach
                        </select>
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
                                    <th>Total PT Exemption Count</th>
                                    <th>Total Stationed Leave Count</th>
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
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script>
    $(document).ready(function() {
        const filters = @json($filters ?? []);
        const baseUrl = "{{ route('admin.dashboard.ot-participants') }}";
        const LOCKED_COLUMNS = [0, 1, 2]; // S.No, OT Code, Name — frozen & always visible
        // Columns shown only on the Present tab (the Absent view is a shorter layout):
        // Topic Name, Total Duty (Count), Duty Type, Total Notice/Memo, Total Discipline Memo.
        const PRESENT_ONLY_COLS = [6, 7, 8, 12, 13];
        function isPresentOnly(idx) { return PRESENT_ONLY_COLS.indexOf(idx) !== -1; }

        // The shared exemption columns are labelled differently per tab:
        // Present shows the "Total … Count" summary wording; Absent uses the short
        // design labels (no "Total"/"Count").
        const HEADER_BY_TAB = {
            9:  { present: 'Total Medical Exemption Count', absent: 'Medical Exemption' },
            10: { present: 'Total PT Exemption Count',      absent: 'PT Exception' },
            11: { present: 'Total Stationed Leave Count',   absent: 'Stationed Leave' },
        };
        function applyTabHeaders() {
            if (!dt) { return; }
            const tab = currentAttendance === 'absent' ? 'absent' : 'present';
            Object.keys(HEADER_BY_TAB).forEach(function(idx) {
                const th = dt.column(parseInt(idx, 10)).header();
                if (th) { $(th).text(HEADER_BY_TAB[idx][tab]); }
            });
        }
        let dt = null;
        let currentAttendance = (filters.attendance === 'absent') ? 'absent' : 'present';
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
            if (currentAttendance && currentAttendance !== 'present') { p.set('attendance', currentAttendance); } else { p.delete('attendance'); }
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

        function padCount(n) { n = parseInt(n, 10) || 0; return (n < 10 ? '0' : '') + n; }
        function updateTabCounts(counts) {
            if (!counts) { return; }
            ['present', 'absent'].forEach(function(k) {
                if (counts[k] !== undefined) { $('.sl-tab-count[data-count="' + k + '"]').text(padCount(counts[k])); }
            });
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
                    d.attendance = currentAttendance;
                }
            },
            columns: [
                { data: 's_no', name: 's_no', orderable: false },
                { data: 'ot_code', name: 'ot_code' },
                { data: 'name', name: 'name' },
                { data: 'email', name: 'email' },
                { data: 'cadre', name: 'cadre' },
                { data: 'house', name: 'house' },
                { data: 'topic', name: 'topic', orderable: false, searchable: false },
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
          .on('xhr.dt', function(e, settings, json) {
              setTableLoading(false);
              if (json && json.counts) { updateTabCounts(json.counts); }
          })
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

        /* ── Present / Absent tabs ── */
        $('.programme-status-tabs .programme-status-pill[data-attendance]').on('click', function() {
            const att = $(this).data('attendance');
            if (att === currentAttendance) { return; }
            currentAttendance = att;
            $('.programme-status-tabs .programme-status-pill').removeClass('active');
            $(this).addClass('active');
            setupOtColumns(); // Present ↔ Absent use different column layouts
            syncUrl();
            if (dt) { dt.ajax.reload(null, true); }
        });

        /* ── Filters ── */
        $('#courseFilter').on('change', function() { applyFilter({ course_id: this.value }); });
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
        });
        $period.on('cancel.daterangepicker', function() { $period.val(''); applyFilter({ from_date: '', to_date: '' }); });

        /* ── Print ── */
        $('#otListPrintBtn').on('click', function() { window.print(); });

        /* ── Dynamic columns: show / hide ── */
        const otColStorageKey = 'otParticipantsGrid:hiddenColumns:v1';
        function otGetHiddenCols() {
            try { const raw = localStorage.getItem(otColStorageKey); const arr = raw ? JSON.parse(raw) : []; return Array.isArray(arr) ? arr : []; }
            catch (e) { return []; }
        }
        function otPersistHiddenCols(arr) { try { localStorage.setItem(otColStorageKey, JSON.stringify(arr)); } catch (e) {} }
        function setupOtColumns() {
            if (!dt) { return; }
            applyTabHeaders(); // set the per-tab header labels before (re)building the modal
            const onAbsent = currentAttendance === 'absent';
            const hidden = otGetHiddenCols().filter(idx => LOCKED_COLUMNS.indexOf(idx) === -1);
            otPersistHiddenCols(hidden);
            dt.columns().every(function() {
                const idx = this.index();
                if (LOCKED_COLUMNS.indexOf(idx) !== -1) { this.visible(true, false); return; }
                // Present-only columns are hidden entirely on the Absent tab; on the
                // Present tab they follow the user's own show/hide preference.
                if (isPresentOnly(idx)) { this.visible(!onAbsent && hidden.indexOf(idx) === -1, false); return; }
                this.visible(hidden.indexOf(idx) === -1, false);
            });
            dt.columns.adjust();
            relayoutPinnedColumns();
            const $grid = $('#otColumnToggleGrid');
            if (!$grid.length) { return; }
            $grid.empty();
            dt.columns().every(function() {
                const idx = this.index();
                if (onAbsent && isPresentOnly(idx)) { return; } // not applicable on the Absent tab
                const title = $(this.header()).text().replace(/\s+/g, ' ').trim();
                if (!title) { return; }
                const isLocked = LOCKED_COLUMNS.indexOf(idx) !== -1;
                const inputId = 'otcolvis_' + idx;
                const $cell = $('<div class="col-12 col-sm-6 col-md-4"></div>');
                const $label = $('<label class="colvis-item d-flex align-items-center gap-2 border rounded-3 px-3 py-2 mb-0 w-100"></label>').attr('for', inputId);
                const $cb = $('<input type="checkbox" class="form-check-input m-0">')
                    .attr('id', inputId)
                    .prop('checked', isLocked || hidden.indexOf(idx) === -1)
                    .prop('disabled', isLocked);
                $cb.on('change', function() {
                    if (isLocked) { return; }
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
