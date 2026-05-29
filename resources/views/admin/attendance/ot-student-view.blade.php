@extends(hasRole('Officer Trainee') ? 'admin.layouts.timetable' : 'admin.layouts.master')

@section('title', 'OT Student Attendance Details')

@section('content')
<style>
/* ===================== Attendance Details — UI ===================== */
.attendance-details .attn-card {
    background: #fff;
    border: 1px solid #eef0f3;
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04);
}
.attendance-details .page-title { font-size: 24px; font-weight: 700; color: #101828; }

/* Info cards */
.attendance-details .info-card { position: relative; overflow: hidden; }
.attendance-details .info-card::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
    border-radius: 14px 0 0 14px;
}
.attendance-details .info-card.accent-blue::before { background: #2f6fed; }
.attendance-details .info-card.accent-green::before { background: #16a34a; }
.attendance-details .info-card.accent-teal::before { background: #0ea5e9; }
.attendance-details .info-label { font-size: 12px; font-weight: 600; color: #8a93a2; letter-spacing: .2px; margin-bottom: 6px; }
.attendance-details .info-value { font-size: 15px; font-weight: 600; color: #1e293b; line-height: 1.45; }

/* View-mode buttons */
.attendance-details .view-btn {
    border: 1px solid #e3e8ef; background: #fff; color: #5a6a7e;
    font-weight: 600; font-size: 14px; padding: 8px 22px; border-radius: 10px;
    cursor: pointer; transition: background .15s ease, color .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.attendance-details .view-btn:hover:not(.active) { background: #f5f7fa; color: #1a3255; }
.attendance-details .view-btn.active { background: #0d47a1; border-color: #0d47a1; color: #fff; box-shadow: 0 2px 8px rgba(13, 71, 161, .25); }

/* Download button */
.attendance-details .download-btn {
    border: 1px solid #cfe0f5; background: #fff; color: #1565c0;
    font-weight: 600; font-size: 14px; padding: 9px 18px; border-radius: 10px;
    cursor: pointer; transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.attendance-details .download-btn:hover { background: #f3f8ff; border-color: #9cc2ee; box-shadow: 0 2px 8px rgba(13, 71, 161, .12); }
.attendance-details .download-btn .dl-caret { font-size: 12px; transition: transform .15s ease; }

/* Download hover dropdown */
.attendance-details .download-dropdown { position: relative; display: inline-block; }
.attendance-details .download-menu {
    position: absolute; right: 0; top: calc(100% + 6px);
    min-width: 170px; margin: 0; padding: 6px; list-style: none;
    background: #fff; border: 1px solid #eaecf0; border-radius: 10px;
    box-shadow: 0 8px 24px rgba(16, 24, 40, .12);
    opacity: 0; visibility: hidden; transform: translateY(-4px);
    transition: opacity .15s ease, transform .15s ease, visibility .15s ease;
    z-index: 1050;
}
.attendance-details .download-menu::before { content: ''; position: absolute; top: -8px; left: 0; right: 0; height: 8px; }
.attendance-details .download-dropdown:hover .download-menu,
.attendance-details .download-dropdown.open .download-menu { opacity: 1; visibility: visible; transform: translateY(0); }
.attendance-details .download-dropdown:hover .dl-caret,
.attendance-details .download-dropdown.open .dl-caret { transform: rotate(180deg); }
.attendance-details .download-item {
    display: flex; align-items: center; gap: 10px; width: 100%;
    background: transparent; border: none; border-radius: 7px; padding: 9px 12px;
    font-size: 14px; font-weight: 500; color: #344054; cursor: pointer; text-align: left;
    transition: background .15s ease, color .15s ease;
}
.attendance-details .download-item:hover { background: #f4f7fb; color: #0d47a1; }
.attendance-details .download-item i { font-size: 16px; }

/* Toolbar */
.attendance-details .toolbar-label { font-size: 14px; font-weight: 500; color: #8a93a2; }
.attendance-details .toolbar-control {
    width: auto;
    min-width: 160px;
    max-width: 220px;
    flex: 0 0 auto;
    border: 1px solid #d8dde5; border-radius: 8px; font-size: 14px; color: #344054;
}
.attendance-details .toolbar-control:focus { border-color: #86b7fe; box-shadow: 0 0 0 3px rgba(13, 110, 253, .12); }
/* select2 (Course filter in Archive mode) — keep it compact, not full width */
.attendance-details .select2-container { width: auto !important; min-width: 200px; }
.attendance-details .select2-container .select2-selection--single { height: calc(1.5em + .5rem + 2px); border-color: #d8dde5; border-radius: 8px; }
.attendance-details .btn-reset { border: 1px solid #e35d6a; color: #d6293e; background: #fff; font-weight: 600; border-radius: 8px; white-space: nowrap; }
.attendance-details .btn-reset:hover { background: #fdecee; color: #b71d2b; border-color: #d6293e; }
.attendance-details .btn-tool { border: 1px solid #d8dde5; color: #475467; background: #fff; font-weight: 500; border-radius: 8px; white-space: nowrap; }
.attendance-details .btn-tool:hover { border-color: #b6bfca; background: #f8fafc; }

/* Expandable client-side search */
.attendance-details #tableSearchWrap #tableSearchInput {
    width: 0; opacity: 0; padding: 0; margin: 0; border: 1px solid transparent;
    transition: width .25s ease, opacity .2s ease, padding .25s ease, margin .2s ease, border-color .15s ease;
}
.attendance-details #tableSearchWrap.open #tableSearchInput {
    width: 200px; opacity: 1; padding: .3rem .65rem; margin-right: 6px; border-color: #d8dde5; border-radius: 8px;
}

/* Table */
.attendance-details #attendanceTable thead th {
    font-size: 13px; font-weight: 500; color: #8a93a2; background: #f8f9fb;
    border-bottom: 1px solid #eef0f3; text-transform: none; vertical-align: middle;
}
.attendance-details #attendanceTable tbody td {
    font-size: 14px; color: #475467; border-bottom: 1px solid #f2f4f7;
    padding-top: 14px; padding-bottom: 14px; vertical-align: middle;
}
.attendance-details #attendanceTable tbody tr:last-child td { border-bottom: none; }
.attendance-details #attendanceTable.table-hover tbody tr:hover { background: #f9fafb; }
.attendance-details #attendanceTable .sub { font-size: 12.5px; color: #98a2b3; font-weight: 400; }
.attendance-details .dash { color: #c3c9d2; }
.attendance-details .status-badge { border-radius: 10px; padding: 6px 14px; font-weight: 600; font-size: 13px; }

/* Pagination footer */
.attendance-details .pagination .page-link {
    border: 1px solid transparent; border-radius: 8px; margin: 0 3px;
    color: #667085; font-size: 13px; min-width: 34px; text-align: center;
}
.attendance-details .pagination .page-item.active .page-link { background: #fff; border-color: #2f6fed; color: #2f6fed; font-weight: 600; }
.attendance-details .pagination .page-link:hover { background: #f2f4f7; }
.attendance-details .pagination .page-item.disabled .page-link { color: #c3c9d2; background: transparent; }
.attendance-details .per-page { border: 1px solid #d8dde5; border-radius: 8px; font-size: 13px; color: #344054; }

/* Column-visibility modal */
.col-vis-modal { border: none; border-radius: 16px; box-shadow: 0 24px 48px rgba(16, 24, 40, .18); }
.col-vis-modal .modal-title { font-size: 22px; font-weight: 700; color: #101828; }
.col-vis-chip {
    border: 1px solid #d5dae1; border-radius: 12px; padding: 14px 16px; cursor: pointer; background: #fff;
    transition: border-color .15s ease, background .15s ease;
}
.col-vis-chip:hover { border-color: #9cc2ee; background: #f7faff; }
.col-vis-chip .form-check-input { width: 18px; height: 18px; border-radius: 5px; }
.col-vis-chip .form-check-input:checked { background-color: #2f6fed; border-color: #2f6fed; }

@media (max-width: 575.98px) {
    .attendance-details .toolbar-control { min-width: 0; max-width: none; width: 100%; }
    .attendance-details #tableSearchWrap.open #tableSearchInput { width: 140px; }
}
</style>
<div class="container-fluid attendance-details">
     @if(hasRole('Training') || hasRole('Super Admin') ||  hasRole('Training MCTP Admin') || hasRole('Training IST'))
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
            <div class="attn-card info-card accent-blue h-100">
                <div class="p-4">
                    <div class="info-label">Course Name</div>
                    <div class="info-value text-break">{{ $course->course_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="attn-card info-card accent-green h-100">
                <div class="p-4">
                    <div class="info-label">Student Name</div>
                    <div class="info-value text-break">{{ $student->display_name ?? 'N/A' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="attn-card info-card accent-teal h-100">
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
        <div class="download-dropdown" id="downloadWrap">
            <button type="button" id="downloadBtn" class="download-btn d-inline-flex align-items-center gap-2"
                aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-download"></i> Download
                <i class="bi bi-chevron-down dl-caret"></i>
            </button>
            <ul class="download-menu" role="menu" aria-label="Download format">
                <li>
                    <button type="button" class="download-item" id="downloadCsv" role="menuitem">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </button>
                </li>
                <li>
                    <button type="button" class="download-item" id="downloadPdf" role="menuitem">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </button>
                </li>
            </ul>
        </div>
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

        // ---- Download dropdown: opens on hover (CSS); click toggles for touch ----
        (function() {
            var wrap = document.getElementById('downloadWrap');
            var btn = document.getElementById('downloadBtn');
            if (!wrap || !btn) return;
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var open = wrap.classList.toggle('open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
            document.addEventListener('click', function(e) {
                if (!wrap.contains(e.target)) {
                    wrap.classList.remove('open');
                    btn.setAttribute('aria-expanded', 'false');
                }
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

        // ---- Download (CSV / PDF export of the full table, respecting hidden columns) ----
        function getExportMatrix() {
            var headCells = table.querySelectorAll('thead th');
            var visibleIdx = [], headers = [];
            headCells.forEach(function(th, i) {
                if (th.style.display !== 'none') {
                    visibleIdx.push(i);
                    headers.push((th.innerText || '').replace(/\s+/g, ' ').trim());
                }
            });
            var body = [];
            tbody.querySelectorAll('tr').forEach(function(tr) {
                if (tr.id === 'noSearchResultRow') return;
                var cells = tr.children;
                body.push(visibleIdx.map(function(i) {
                    var c = cells[i];
                    return c ? (c.innerText || '').replace(/\s+/g, ' ').trim() : '';
                }));
            });
            return { headers: headers, body: body };
        }

        function triggerDownload(blob, filename) {
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url; a.download = filename;
            document.body.appendChild(a); a.click(); document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }

        function escapeHtml(s) {
            return String(s).replace(/[&<>"']/g, function(ch) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
            });
        }

        function exportCsv() {
            var m = getExportMatrix();
            function cell(t) { return '"' + String(t).replace(/"/g, '""') + '"'; }
            var lines = [m.headers.map(cell).join(',')];
            m.body.forEach(function(r) { lines.push(r.map(cell).join(',')); });
            triggerDownload(new Blob(["﻿" + lines.join('\n')], { type: 'text/csv;charset=utf-8;' }), 'attendance-details.csv');
        }

        function exportPdf() {
            var m = getExportMatrix();
            if (typeof pdfMake !== 'undefined' && pdfMake.createPdf) {
                var bodyArr = [m.headers.map(function(h) { return { text: h, style: 'th' }; })];
                m.body.forEach(function(r) { bodyArr.push(r.map(function(c) { return { text: c, style: 'td' }; })); });
                pdfMake.createPdf({
                    pageOrientation: 'landscape', pageSize: 'A4', pageMargins: [20, 24, 20, 24],
                    content: [
                        { text: 'Attendance Details', style: 'title' },
                        { table: { headerRows: 1, widths: m.headers.map(function() { return 'auto'; }), body: bodyArr }, layout: 'lightHorizontalLines' }
                    ],
                    styles: {
                        title: { fontSize: 15, bold: true, margin: [0, 0, 0, 10] },
                        th: { bold: true, fontSize: 9, fillColor: '#f3f4f6', color: '#1f2937' },
                        td: { fontSize: 8, color: '#374151' }
                    },
                    defaultStyle: { fontSize: 8 }
                }).download('attendance-details.pdf');
                return;
            }
            // Fallback when pdfMake is unavailable: print window (Save as PDF)
            var w = window.open('', '_blank');
            if (!w) return;
            var th = m.headers.map(function(h) { return '<th>' + escapeHtml(h) + '</th>'; }).join('');
            var rows = m.body.map(function(r) {
                return '<tr>' + r.map(function(c) { return '<td>' + escapeHtml(c) + '</td>'; }).join('') + '</tr>';
            }).join('');
            w.document.write(
                '<html><head><title>Attendance Details</title><style>' +
                'body{font-family:Arial,sans-serif;padding:20px;}h2{margin:0 0 14px;}' +
                'table{border-collapse:collapse;width:100%;font-size:11px;}' +
                'th,td{border:1px solid #d0d5dd;padding:6px 8px;text-align:left;}th{background:#f3f4f6;}' +
                '</style></head><body><h2>Attendance Details</h2><table><thead><tr>' + th + '</tr></thead><tbody>' + rows + '</tbody></table>' +
                '<scr' + 'ipt>window.onload=function(){window.print();}</scr' + 'ipt></body></html>'
            );
            w.document.close();
        }

        function closeDownloadMenu() {
            var wrap = document.getElementById('downloadWrap');
            if (wrap) wrap.classList.remove('open');
        }

        var csvBtn = document.getElementById('downloadCsv');
        var pdfBtn = document.getElementById('downloadPdf');
        if (csvBtn) csvBtn.addEventListener('click', function() { exportCsv(); closeDownloadMenu(); });
        if (pdfBtn) pdfBtn.addEventListener('click', function() { exportPdf(); closeDownloadMenu(); });
    });
    </script>
</div>
@endsection

@push('scripts')
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
@endpush
