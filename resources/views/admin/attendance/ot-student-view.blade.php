@extends(hasRole('Student-OT') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'OT Student Attendance Details')

@section('content')
<style>
    .attendance-page .attn-card {
        background: #fff;
        border: 0 !important;
        border-radius: 14px !important;
        box-shadow: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04) !important;
    }
    .attendance-page .page-title { font-weight: 700; font-size: 1.7rem; color: #111827; }

    /* Info cards */
    .attendance-page .info-card { border-left: 4px solid #1d4ed8 !important; transition: transform .15s ease, box-shadow .15s ease; }
    .attendance-page .info-card.accent-blue { border-left-color: #2563eb !important; }
    .attendance-page .info-card.accent-teal { border-left-color: #0dcaf0 !important; }
    .attendance-page .info-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(16, 24, 40, .1) !important; }
    .attendance-page .info-label { font-size: .8rem; color: #6b7280; font-weight: 500; margin-bottom: .25rem; }
    .attendance-page .info-value { font-weight: 700; color: #111827; font-size: .98rem; }

    /* View toggle + Download */
    .attendance-page .view-btn { border: 1px solid #d7dce5; background: #fff; color: #1f2937; border-radius: .6rem; padding: .5rem 1.25rem; font-weight: 600; font-size: .92rem; transition: all .15s ease; }
    .attendance-page .view-btn.active { background: #0d4d92; border-color: #0d4d92; color: #fff; box-shadow: 0 .25rem .6rem rgba(13, 77, 146, .2); }
    .attendance-page .view-btn:hover:not(.active) { background: #f1f4f9; }
    .attendance-page .download-btn { border: 1px solid #d7dce5; background: #fff; color: #0d4d92; border-radius: .6rem; padding: .5rem 1.1rem; font-weight: 600; font-size: .92rem; transition: all .15s ease; }
    .attendance-page .download-btn:hover { background: #f1f4f9; }

    /* Filter toolbar */
    .attendance-page .toolbar-label { color: #6b7280; font-size: .9rem; }
    .attendance-page .toolbar-control { min-width: 150px; max-width: 190px; border: 1px solid #d7dce5; border-radius: .6rem; color: #374151; font-weight: 500; }
    .attendance-page .toolbar-control:focus { border-color: #0d4d92; box-shadow: 0 0 0 .2rem rgba(13, 77, 146, .12); }
    .attendance-page .btn-tool { border: 1px solid #d7dce5; background: #fff; color: #374151; border-radius: .6rem; font-weight: 600; font-size: .88rem; }
    .attendance-page .btn-tool:hover { background: #f1f4f9; }
    /* Column Visibility modal */
    .col-vis-modal { border: 0; border-radius: 16px; box-shadow: 0 1.5rem 3rem rgba(16, 24, 40, .2); }
    .col-vis-modal .modal-title { color: #111827; }
    .col-vis-chip { border: 1px solid #e2e6ee; border-radius: 10px; padding: .55rem .75rem; cursor: pointer; transition: border-color .15s ease, background .15s ease; color: #374151; }
    .col-vis-chip:hover { border-color: #0d4d92; background: #f8fafc; }
    .col-vis-chip:has(input:checked) { border-color: #0d4d92; background: #f1f6fc; }
    /* Expandable table search */
    .attendance-page #tableSearchInput { width: 0; min-width: 0; padding-left: 0; padding-right: 0; margin-right: 0; border-color: transparent; opacity: 0; overflow: hidden; transition: width .2s ease, opacity .2s ease, padding .2s ease, margin .2s ease; }
    .attendance-page #tableSearchWrap.open #tableSearchInput { width: 200px; min-width: 200px; padding-left: .6rem; padding-right: .6rem; margin-right: .4rem; border: 1px solid #d7dce5; border-radius: .6rem; opacity: 1; }
    .attendance-page #tableSearchInput:focus { border-color: #0d4d92; box-shadow: 0 0 0 .2rem rgba(13, 77, 146, .12); }
    .attendance-page .btn-reset { border: 1px solid #ef4444; background: #fff; color: #ef4444; border-radius: .6rem; font-weight: 600; font-size: .88rem; }
    .attendance-page .btn-reset:hover { background: #ef4444; color: #fff; }

    /* Table */
    .attendance-page .table thead th { background: #f4f6fa; color: #6b7280; font-weight: 600; font-size: .8rem; border-bottom: 1px solid #e6e9f0; text-transform: none; letter-spacing: 0; white-space: nowrap; }
    .attendance-page .table tbody td { vertical-align: middle; border-color: #eef1f6; font-size: .9rem; color: #1f2937; }
    .attendance-page .table tbody td .sub { color: #9ca3af; font-size: .8rem; }
    .attendance-page .dash { color: #cbd5e1; }

    /* Status pills */
    .attendance-page .status-badge { border-radius: 999px; padding: .4rem .9rem; font-size: .78rem; font-weight: 600; min-width: 84px; }
    .attendance-page .status-badge.bg-success-subtle { background: #dcfce7 !important; }
    .attendance-page .status-badge.text-success-emphasis { color: #16a34a !important; }
    .attendance-page .status-badge.bg-danger-subtle { background: #fee2e2 !important; }
    .attendance-page .status-badge.text-danger-emphasis { color: #dc2626 !important; }
    .attendance-page .status-badge.bg-warning-subtle { background: #fef3c7 !important; }
    .attendance-page .status-badge.text-warning-emphasis { color: #d97706 !important; }
    .attendance-page .status-badge.bg-secondary-subtle { background: #f1f5f9 !important; }
    .attendance-page .status-badge.text-secondary-emphasis { color: #64748b !important; }

    /* Pagination */
    .attendance-page .pagination .page-link { border: 1px solid transparent; background: transparent; color: #374151; border-radius: 8px; margin: 0 2px; min-width: 34px; text-align: center; }
    .attendance-page .pagination .page-item.active .page-link { border-color: #6366f1; color: #4f46e5; background: #fff; font-weight: 600; }
    .attendance-page .pagination .page-item:first-child .page-link,
    .attendance-page .pagination .page-item:last-child .page-link { border-color: #e6e9f0; color: #9ca3af; }
    .attendance-page .pagination .page-link:focus { box-shadow: none; }
    .attendance-page .per-page { border: 1px solid #d7dce5; }
</style>

<div class="container-fluid attendance-page p-0 px-2 py-2">
    @if(hasRole('Training') || hasRole('Admin') || hasRole('Training-MCTP') || hasRole('IST'))
    <x-breadcrum title="My Attendance Record" />
    <x-session_message />
    @endif

    @php $recordCount = count($attendanceRecords); $isArchive = ($archiveMode ?? 'active') === 'archive'; @endphp

    {{-- Page Title --}}
    <div class="attn-card mb-4">
        <div class="py-4 px-4">
            <h1 class="page-title mb-0">Attendance Details</h1>
        </div>
    </div>

    {{-- Student Information Header --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="attn-card info-card h-100">
                <div class="p-4">
                    <div class="info-label">Course Name</div>
                    <div class="info-value text-break">{{ $course->course_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="attn-card info-card accent-blue h-100">
                <div class="p-4">
                    <div class="info-label">Student Name</div>
                    <div class="info-value text-break">{{ $student->display_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="attn-card info-card accent-blue h-100">
                <div class="p-4">
                    <div class="info-label">OT Code</div>
                    <div class="info-value text-break">{{ $student->generated_OT_code ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- View Mode toggle + Download --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div class="d-flex gap-2" role="group" aria-label="Attendance View Mode">
            <button type="button" id="filterActive" class="view-btn {{ !$isArchive ? 'active' : '' }}"
                aria-pressed="{{ !$isArchive ? 'true' : 'false' }}">
                Active{{ !$isArchive ? ': '.$recordCount : '' }}
            </button>
            <button type="button" id="filterArchive" class="view-btn {{ $isArchive ? 'active' : '' }}"
                aria-pressed="{{ $isArchive ? 'true' : 'false' }}">
                Archive{{ $isArchive ? ': '.$recordCount : '' }}
            </button>
        </div>
        <button type="button" id="downloadBtn" class="download-btn d-inline-flex align-items-center gap-2">
            <i class="bi bi-download"></i> Download
        </button>
    </div>

    {{-- Attendance Details Table --}}
    <div class="attn-card overflow-hidden">

        {{-- Filter toolbar --}}
        <div class="px-4 py-3 border-bottom">
            <form method="GET" action="{{ route('attendance.OT.student_mark.student', [
                'group_pk' => $group_pk,
                'course_pk' => $course_pk,
                'timetable_pk' => $timetable_pk,
                'student_pk' => $student_pk
            ]) }}" id="filterForm">
                <input type="hidden" name="archive_mode" id="archive_mode_input" value="{{ $archiveMode ?? 'active' }}">

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="toolbar-label me-1">Filters</span>

                    {{-- Course Filter - Only show in Archive mode --}}
                    @if($isArchive)
                    <select class="form-select form-select-sm toolbar-control select2 flex-grow-0" id="filter_course"
                        name="filter_course" aria-label="Filter by Course">
                        <option value="">-- Select Course --</option>
                        @foreach($archivedCourses as $archivedCourse)
                        <option value="{{ $archivedCourse->pk }}"
                            {{ $filterCourse == $archivedCourse->pk ? 'selected' : '' }}>
                            {{ $archivedCourse->course_name }}
                        </option>
                        @endforeach
                    </select>
                    @endif

                    <input type="date" class="form-control form-control-sm toolbar-control flex-grow-0" id="filter_date"
                        name="filter_date" value="{{ $filterDate ?? date('Y-m-d') }}" max="{{ date('Y-m-d') }}"
                        aria-label="Filter by Date">

                    <select class="form-select form-select-sm toolbar-control flex-grow-0" id="filter_status"
                        name="filter_status" aria-label="Filter by Attendance Status">
                        <option value="">-- All Status --</option>
                        <option value="Present" {{ $filterStatus == 'Present' ? 'selected' : '' }}>Present</option>
                        <option value="Late" {{ $filterStatus == 'Late' ? 'selected' : '' }}>Late</option>
                        <option value="Absent" {{ $filterStatus == 'Absent' ? 'selected' : '' }}>Absent</option>
                        <option value="Not Marked" {{ $filterStatus == 'Not Marked' ? 'selected' : '' }}>Not Marked</option>
                    </select>

                    <button type="button" class="btn btn-sm btn-reset d-inline-flex align-items-center gap-1"
                        id="clearFilters">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset Filters
                    </button>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        {{-- Columns toggle (opens Column Visibility modal) --}}
                        @php $columns = ['S. No.', 'Date & Time', 'Venue', 'Group', 'Topic', 'Faculty', 'Attendance Status', 'Duty Type', 'Exemption', 'Doc / Comment']; @endphp
                        <button class="btn btn-sm btn-tool d-inline-flex align-items-center gap-2"
                            type="button" id="columnsBtn" data-bs-toggle="modal"
                            data-bs-target="#columnVisibilityModal">
                            Columns <i class="bi bi-layout-three-columns"></i>
                        </button>

                        {{-- Table search (client-side, expandable) --}}
                        <div class="d-inline-flex align-items-center" id="tableSearchWrap">
                            <input type="text" id="tableSearchInput" class="form-control form-control-sm"
                                placeholder="Search records…" aria-label="Search attendance table"
                                autocomplete="off">
                            <button type="button" class="btn btn-sm btn-tool d-inline-flex align-items-center"
                                id="tableSearchBtn" title="Search records" aria-label="Search records"
                                aria-expanded="false">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if($recordCount > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="attendanceTable">
                <thead>
                    <tr>
                        <th class="text-center py-3 px-4">S. No.</th>
                        <th class="text-nowrap py-3">Date &amp; time</th>
                        <th class="py-3">Venue</th>
                        <th class="py-3">Group</th>
                        <th class="py-3">Topic</th>
                        <th class="py-3">Faculty</th>
                        <th class="text-center text-nowrap py-3">Attendance Status</th>
                        <th class="text-center text-nowrap py-3">Duty Type (MDO/ Escort)</th>
                        <th class="text-center py-3">Exemption</th>
                        <th class="text-center py-3 px-4">Doc/ Comment</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendanceRecords as $record)
                    <tr>
                        <td class="text-center text-muted fw-semibold px-4">{{ $loop->iteration }}</td>
                        <td class="fw-semibold text-nowrap">
                            <div class="d-flex flex-column">
                                <span>{{ $record['date'] }}</span>
                                <span class="sub">{{ $record['session_time'] }}</span>
                            </div>
                        </td>
                        <td>{{ $record['venue'] }}</td>
                        <td>{{ $record['group'] }}</td>
                        <td>{{ $record['topic'] }}</td>
                        <td>{{ $record['faculty'] }}</td>

                        <td class="text-center">
                            @php
                            $status = $record['attendance_status'];
                            $color = '';
                            if ($status == 'Present') {
                            $color = 'success';
                            } elseif ($status == 'Late') {
                            $color = 'warning';
                            } elseif ($status == 'Absent') {
                            $color = 'danger';
                            } else {
                            $color = 'secondary';
                            }
                            @endphp
                            <span class="badge status-badge bg-{{ $color }}-subtle text-{{ $color }}-emphasis">
                                {{ $status }}
                            </span>
                        </td>

                        <td class="text-center">
                            @if($record['duty_type'])
                            <span
                                class="badge rounded-pill bg-info-subtle text-info-emphasis border border-info-subtle fw-semibold py-2 px-3">{{ $record['duty_type'] }}</span>
                            @else
                            <span class="dash">-</span>
                            @endif
                        </td>

                        <td class="text-center">
                            @if($record['exemption_type'])
                            <span
                                class="badge rounded-pill bg-primary-subtle text-primary-emphasis border border-primary-subtle fw-semibold py-2 px-3">{{ $record['exemption_type'] }}</span>
                            @else
                            <span class="dash">-</span>
                            @endif
                        </td>

                        <td class="text-center text-nowrap px-4">
                            @if($record['exemption_document'])
                            <a href="{{ asset('storage/' . $record['exemption_document']) }}" target="_blank"
                                class="btn btn-sm btn-outline-primary me-2" title="View Document"
                                aria-label="View Exemption Document">
                                <i class="bi bi-eye-fill"></i>
                            </a>
                            @endif

                            @if($record['exemption_comment'])
                            @if($record['exemption_document'])
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip"
                                data-bs-placement="top" title="{{ $record['exemption_comment'] }}"
                                aria-label="View Comment">
                                <i class="bi bi-chat-text-fill"></i>
                            </button>
                            @else
                            <span class="text-muted small" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="{{ $record['exemption_comment'] }}">{{ Str::limit($record['exemption_comment'], 15) }}</span>
                            @endif
                            @else
                            @if(!$record['exemption_document'])
                            <span class="dash">-</span>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination footer --}}
        <div class="px-4 py-3 border-top">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <nav aria-label="Attendance pagination">
                    <ul class="pagination pagination-sm mb-0" id="tablePager"></ul>
                </nav>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <span>Showing</span>
                    <select id="perPageSelect" class="form-select form-select-sm per-page w-auto"
                        aria-label="Rows per page">
                        <option value="6">6</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>of <span id="totalItems">{{ $recordCount }}</span> items</span>
                </div>
            </div>
        </div>
        @else
        <div class="text-center text-muted py-5 px-4">
            <i class="bi bi-calendar-x display-5 d-block mb-3 text-secondary opacity-50"></i>
            <h6 class="fw-semibold mb-1">No attendance records found</h6>
            <p class="mb-0 small">Try adjusting the date, status, or view mode filters above.</p>
        </div>
        @endif
    </div>

    {{-- Column Visibility modal --}}
    <div class="modal fade" id="columnVisibilityModal" tabindex="-1"
        aria-labelledby="columnVisibilityLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content col-vis-modal">
                <div class="modal-header border-0 pb-2">
                    <h5 class="modal-title fw-bold" id="columnVisibilityLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <hr class="mt-0 mb-0 mx-3">
                <div class="modal-body">
                    <div class="row g-2">
                        @foreach($columns as $i => $colName)
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="col-vis-chip d-flex align-items-center gap-2 mb-0">
                                <input type="checkbox" class="form-check-input m-0 column-toggle"
                                    data-col="{{ $i }}" checked>
                                <span class="small text-truncate">{{ $colName }}</span>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 pt-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Front-end enhancements: tooltips, client-side pagination, column toggle, CSV download --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tooltips (guarded: a missing/blocked Bootstrap must not kill the rest of the script)
        try {
            if (window.bootstrap && bootstrap.Tooltip) {
                [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                    .forEach(function(el) { new bootstrap.Tooltip(el); });
            }
        } catch (e) { /* ignore tooltip init errors */ }

        // ---- Expandable client-side search (wired before the table guard so the
        //      icon always toggles, even when the current view has no records) ----
        var applySearch = function() {};   // replaced with the real impl once a table exists
        (function() {
            var wrap = document.getElementById('tableSearchWrap');
            var btn = document.getElementById('tableSearchBtn');
            var input = document.getElementById('tableSearchInput');
            if (!wrap || !btn || !input) return;
            btn.addEventListener('click', function() {
                var open = wrap.classList.toggle('open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open) {
                    input.focus();
                } else if (input.value) {   // clear search on collapse
                    input.value = '';
                    applySearch('');
                }
            });
            input.addEventListener('input', function() { applySearch(this.value); });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') e.preventDefault();   // keep it client-side
                if (e.key === 'Escape') btn.click();         // collapse + clear
            });
        })();

        var table = document.getElementById('attendanceTable');
        if (!table) return;

        // ---- Client-side pagination (display only) ----
        var tbody = table.querySelector('tbody');
        var rows = Array.prototype.slice.call(tbody.querySelectorAll('tr'));
        var perPageSelect = document.getElementById('perPageSelect');
        var pager = document.getElementById('tablePager');
        var totalItemsEl = document.getElementById('totalItems');
        var perPage = parseInt(perPageSelect.value, 10) || 10;
        var currentPage = 1;

        // Pre-compute a lowercased text haystack per row for client-side search
        var haystacks = rows.map(function(r) {
            return (r.textContent || '').toLowerCase().replace(/\s+/g, ' ');
        });
        var query = '';

        function getFiltered() {
            if (!query) return rows;
            return rows.filter(function(r, i) {
                return haystacks[i].indexOf(query) !== -1;
            });
        }

        function renderRows() {
            var data = getFiltered();
            var total = data.length;
            if (totalItemsEl) totalItemsEl.textContent = total;

            var pages = Math.max(1, Math.ceil(total / perPage));
            if (currentPage > pages) currentPage = pages;
            var start = (currentPage - 1) * perPage;
            var end = start + perPage;

            // Hide everything, then reveal the current page of the filtered set
            rows.forEach(function(r) { r.style.display = 'none'; });
            data.slice(start, end).forEach(function(r) { r.style.display = ''; });

            // No-match placeholder row
            var noRes = document.getElementById('noSearchResultRow');
            if (total === 0) {
                if (!noRes) {
                    noRes = document.createElement('tr');
                    noRes.id = 'noSearchResultRow';
                    var td = document.createElement('td');
                    td.colSpan = 10;
                    td.className = 'text-center text-muted py-4';
                    td.textContent = 'No records match your search.';
                    noRes.appendChild(td);
                    tbody.appendChild(noRes);
                }
                noRes.style.display = '';
            } else if (noRes) {
                noRes.style.display = 'none';
            }

            buildPager(pages);
        }

        function makeItem(label, page, opts) {
            opts = opts || {};
            var li = document.createElement('li');
            li.className = 'page-item' + (opts.active ? ' active' : '') + (opts.disabled ? ' disabled' : '');
            var a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.innerHTML = label;
            a.addEventListener('click', function(e) {
                e.preventDefault();
                if (opts.disabled || opts.active) return;
                currentPage = page;
                renderRows();
            });
            li.appendChild(a);
            pager.appendChild(li);
        }

        function buildPager(pages) {
            if (!pager) return;
            pager.innerHTML = '';
            makeItem('&laquo;', currentPage - 1, { disabled: currentPage === 1 });

            var list = [];
            for (var p = 1; p <= pages; p++) {
                if (p === 1 || p === pages || (p >= currentPage - 1 && p <= currentPage + 1)) {
                    list.push(p);
                } else if (list[list.length - 1] !== '...') {
                    list.push('...');
                }
            }
            list.forEach(function(p) {
                if (p === '...') {
                    var li = document.createElement('li');
                    li.className = 'page-item disabled';
                    li.innerHTML = '<span class="page-link">&hellip;</span>';
                    pager.appendChild(li);
                } else {
                    makeItem(String(p), p, { active: p === currentPage });
                }
            });

            makeItem('&raquo;', currentPage + 1, { disabled: currentPage === pages });
        }

        if (perPageSelect) {
            perPageSelect.addEventListener('change', function() {
                perPage = parseInt(this.value, 10) || 10;
                currentPage = 1;
                renderRows();
            });
        }
        // Connect the search box (wired above the table guard) to the renderer
        applySearch = function(val) {
            query = (val || '').trim().toLowerCase();
            currentPage = 1;
            renderRows();
        };
        renderRows();

        // ---- Column show/hide toggle ----
        document.querySelectorAll('.column-toggle').forEach(function(cb) {
            cb.addEventListener('change', function() {
                var idx = parseInt(this.dataset.col, 10);
                var disp = this.checked ? '' : 'none';
                table.querySelectorAll('tr').forEach(function(tr) {
                    var cell = tr.children[idx];
                    if (cell) cell.style.display = disp;
                });
            });
        });

        // ---- Download (CSV export of full table, current filtered view) ----
        var downloadBtn = document.getElementById('downloadBtn');
        if (downloadBtn) {
            downloadBtn.addEventListener('click', function() {
                var csv = [];
                table.querySelectorAll('tr').forEach(function(tr) {
                    var cells = Array.prototype.slice.call(tr.children).filter(function(c) {
                        return c.style.display !== 'none';
                    });
                    var line = cells.map(function(c) {
                        var t = (c.innerText || '').replace(/\s+/g, ' ').trim();
                        return '"' + t.replace(/"/g, '""') + '"';
                    });
                    csv.push(line.join(','));
                });
                var blob = new Blob(["﻿" + csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'attendance-details.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
            });
        }
    });
    </script>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2').select2({
            placeholder: 'Select an option',
            allowClear: true,
            width: 'resolve'
        });
    }

    // Active/Archive toggle button handlers
    $('#filterActive').on('click', function() {
        setActiveButton($(this));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });

    $('#filterArchive').on('click', function() {
        setActiveButton($(this));
        $('#archive_mode_input').val('archive');
        $('#filterForm').submit();
    });

    // Function to set active button styling
    function setActiveButton(activeBtn) {
        // Reset all buttons to outline style
        $('#filterActive')
            .removeClass('btn-success active text-white')
            .addClass('btn-outline-success')
            .attr('aria-pressed', 'false');

        $('#filterArchive')
            .removeClass('btn-secondary active text-white')
            .addClass('btn-outline-secondary')
            .attr('aria-pressed', 'false');

        // Set the active button
        if (activeBtn.attr('id') === 'filterActive') {
            activeBtn.removeClass('btn-outline-success')
                .addClass('btn-success text-white active')
                .attr('aria-pressed', 'true');
        } else if (activeBtn.attr('id') === 'filterArchive') {
            activeBtn.removeClass('btn-outline-secondary')
                .addClass('btn-secondary text-white active')
                .attr('aria-pressed', 'true');
        }
    }

    // Auto-submit form when filters change
    let filterTimeout;
    $('#filter_date, #filter_course, #filter_status').on('change', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(function() {
            $('#filterForm').submit();
        }, 300);
    });

    // Clear filters button
    $('#clearFilters').on('click', function() {
        $('#filter_date').val('');
        $('#filter_status').val('');
        const statusSelect = $('#filter_status');
        if ($.fn.select2 && statusSelect.hasClass('select2-hidden-accessible')) {
            statusSelect.select2('val', '');
        }
        const courseSelect = $('#filter_course');
        if (courseSelect.length) {
            courseSelect.val('').trigger('change');
            if ($.fn.select2 && courseSelect.hasClass('select2-hidden-accessible')) {
                courseSelect.select2('val', '');
            }
        }
        setActiveButton($('#filterActive'));
        $('#archive_mode_input').val('active');
        $('#filterForm').submit();
    });
});
</script>
@endsection
