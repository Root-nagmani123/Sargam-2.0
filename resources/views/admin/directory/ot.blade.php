@extends('admin.layouts.master')

@section('title', 'OT Directory')

@section('content')
<div class="container-fluid ot-directory">
    <x-breadcrum title="OT Directory"></x-breadcrum>

    <form method="GET" action="{{ route('admin.directory.ot') }}" id="otFilterForm">
        {{-- Default submit (plain filter) so pressing Enter never triggers an export button --}}
        <button type="submit" class="visually-hidden" tabindex="-1" aria-hidden="true">Apply</button>

        {{-- ===== Download (export) — top right, above card ===== --}}
        <div class="d-flex justify-content-end mb-3">
            <div class="dropdown">
                <button type="button" class="ot-download-btn" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false">
                    <i class="material-icons material-symbols-rounded">download</i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end ot-dropdown-menu">
                    <li>
                        <button type="submit" name="export" value="csv" class="dropdown-item d-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded">description</i> CSV
                        </button>
                    </li>
                    <li>
                        <button type="submit" name="export" value="excel" class="dropdown-item d-flex align-items-center gap-2">
                            <i class="material-icons material-symbols-rounded">grid_on</i> Excel
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="card border-0 ot-card">
            <div class="card-body p-0">

                {{-- ===== Toolbar ===== --}}
                <div class="ot-toolbar d-flex flex-wrap align-items-center gap-2 px-3 px-lg-4 py-3">
                    <span class="ot-filters-text">Filters</span>

                    <div class="ot-select-wrap">
                        <select name="course_id" id="otCourseSelect" class="ot-select"
                                aria-label="Program Name"
                                onchange="document.getElementById('otFilterForm').submit()">
                            <option value="">Program Name</option>
                            @foreach($activeCourses as $course)
                                <option value="{{ $course->pk }}" {{ (int) $selectedCourseId === (int) $course->pk ? 'selected' : '' }}>
                                    {{ $course->couse_short_name ?: $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <a href="{{ route('admin.directory.ot', ['course_id' => $selectedCourseId]) }}" class="ot-reset-btn">
                        <i class="material-icons material-symbols-rounded">refresh</i> Reset Filters
                    </a>

                    <div class="ms-auto d-flex align-items-center gap-2">
                        {{-- Columns visibility --}}
                        <button type="button" id="otColumnsBtn" class="ot-columns-btn"
                                data-bs-toggle="modal" data-bs-target="#otColumnsModal">
                            <span>Columns</span><i class="material-icons material-symbols-rounded">view_column</i>
                        </button>

                        {{-- Expandable search --}}
                        <div class="ot-search-wrap {{ ($search ?? '') !== '' ? 'expanded' : '' }}" id="otSearchWrap">
                            <input
                                id="otSearchInput"
                                type="text"
                                name="search"
                                value="{{ $search ?? '' }}"
                                class="ot-search-field"
                                placeholder="Search name, OT code, email, cadre…"
                                autocomplete="off"
                            >
                            <button type="button" class="ot-search-btn btn-sm" id="otSearchToggle" aria-label="Search">
                                <i class="material-icons material-symbols-rounded">search</i>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- ===== Table ===== --}}
                <div class="table-responsive ot-directory-scroll">
                    <table class="table align-middle mb-0 datatable" id="otDirectoryTable" data-export="false">
                        <thead>
                            <tr>
                                <th class="ot-col-sno">S. No.</th>
                                <th>Name</th>
                                <th>OT Code</th>
                                <th>Room No.</th>
                                <th>Room Extension No.</th>
                                <th>Email ID</th>
                                <th>Course Name</th>
                                <th>Cadre Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td class="ot-col-sno">{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="ot-name-cell">
                                            @if(!empty($student->photo_path))
                                                <img
                                                    src="{{ asset('storage/' . $student->photo_path) }}"
                                                    alt="{{ $student->display_name ?: 'photo' }}"
                                                    class="ot-avatar-photo directory-photo"
                                                    loading="lazy"
                                                    decoding="async"
                                                >
                                            @else
                                                <span class="ot-avatar-letter">{{ strtoupper(mb_substr($student->display_name ?: '?', 0, 1)) }}</span>
                                            @endif
                                            <span class="ot-student-name">{{ $student->display_name ?: '-' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $student->generated_OT_code ?: '-' }}</td>
                                    <td>-</td>
                                    <td>-</td>
                                    <td>{{ $student->email ?: '-' }}</td>
                                    <td class="ot-course-cell">{{ $student->course_name ?: '-' }}</td>
                                    <td>{{ $student->cadre_name ?: '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </form>

    {{-- ===== Column Visibility modal ===== --}}
    <div class="modal fade ot-columns-modal" id="otColumnsModal" tabindex="-1" aria-labelledby="otColumnsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="otColumnsModalLabel">Column Visibility</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="ot-col-grid" id="otColumnsGrid"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn ot-modal-close-btn" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
/* ===== Page / Card ===== */
.ot-directory .ot-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(16, 24, 40, .06), 0 1px 2px rgba(16, 24, 40, .04);
    overflow: hidden;
}

/* ===== Download button (above card) ===== */
.ot-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border: 1px solid #cfe0f5;
    border-radius: 8px;
    background: #fff;
    color: #1565c0;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.2;
    cursor: pointer;
    transition: background .15s ease, border-color .15s ease, box-shadow .15s ease;
}
.ot-download-btn:hover,
.ot-download-btn:focus {
    background: #f3f8ff;
    border-color: #9cc2ee;
    box-shadow: 0 2px 8px rgba(13, 71, 161, .12);
}
.ot-download-btn i { font-size: 16px; }

/* ===== Toolbar ===== */
.ot-toolbar {
    background: #fff;
    border-bottom: 1px solid #eef0f3;
}
.ot-filters-text {
    font-size: 14px;
    font-weight: 500;
    color: #8a93a2;
    margin-right: 2px;
}

/* Program select */
.ot-select-wrap { position: relative; }
.ot-select {
    appearance: none;
    -webkit-appearance: none;
    padding: 9px 36px 9px 14px;
    border: 1px solid #d8dde5;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 500;
    color: #344054;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M3 4.5l3 3 3-3' stroke='%23667085' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 12px center;
    cursor: pointer;
    min-width: 180px;
    max-width: 260px;
    text-overflow: ellipsis;
    transition: border-color .15s ease, box-shadow .15s ease;
}
.ot-select:focus {
    outline: none;
    border-color: #86b7fe;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
}

/* Reset Filters */
.ot-reset-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1px solid #e35d6a;
    border-radius: 8px;
    background: #fff;
    color: #d6293e;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.2;
    text-decoration: none;
    transition: background .15s ease, color .15s ease, border-color .15s ease;
}
.ot-reset-btn:hover {
    background: #fdecee;
    color: #b71d2b;
    border-color: #d6293e;
}
.ot-reset-btn i { font-size: 15px; }

/* Columns button */
.ot-columns-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 14px;
    border: 1px solid #d8dde5;
    border-radius: 8px;
    background: #fff;
    color: #475467;
    font-size: 14px;
    font-weight: 500;
    line-height: 1.2;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease;
}
.ot-columns-btn:hover { border-color: #b6bfca; background: #f8fafc; }
.ot-columns-btn i { font-size: 16px; color: #667085; }

/* Search (expandable) */
.ot-search-wrap {
    display: flex;
    align-items: center;
    justify-content: flex-end;
}
.ot-search-field {
    width: 0;
    opacity: 0;
    padding: 0;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    outline: none;
    transition: width .25s ease, opacity .2s ease, padding .25s ease, border-color .15s ease;
}
.ot-search-wrap.expanded .ot-search-field {
    width: 220px;
    opacity: 1;
    padding: 8px 12px;
    margin-right: 8px;
    border-color: #d8dde5;
}
.ot-search-wrap.expanded .ot-search-field:focus {
    border-color: #86b7fe;
    box-shadow: 0 0 0 3px rgba(13, 110, 253, .12);
}
.ot-search-btn {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border: 1px solid #d8dde5;
    border-radius: 8px;
    background: #fff;
    color: #667085;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, color .15s ease;
}
.ot-search-btn:hover { border-color: #b6bfca; background: #f8fafc; color: #344054; }
.ot-search-btn i { font-size: 17px; }

/* Dropdown menus */
.ot-dropdown-menu {
    border: 1px solid #eaecf0;
    border-radius: 10px;
    box-shadow: 0 8px 24px rgba(16, 24, 40, .12);
    padding: 6px;
    font-size: 14px;
    min-width: 180px;
}
.ot-dropdown-menu .dropdown-item {
    border-radius: 7px;
    padding: 8px 10px;
    color: #344054;
    cursor: pointer;
}
.ot-dropdown-menu .dropdown-item:hover { background: #f4f7fb; }

/* ===== Column Visibility modal ===== */
.ot-columns-modal .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 24px 48px rgba(16, 24, 40, .18);
    overflow: hidden;
}
.ot-columns-modal .modal-header {
    border-bottom: 1px solid #eceff3;
    padding: 22px 28px;
}
.ot-columns-modal .modal-title {
    font-size: 22px;
    font-weight: 700;
    color: #101828;
}
.ot-columns-modal .modal-body { padding: 24px 28px; }
.ot-columns-modal .modal-footer {
    border-top: 0;
    padding: 8px 28px 26px;
}
.ot-col-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
}
.ot-col-chip {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
    padding: 16px 18px;
    border: 1px solid #d5dae1;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 500;
    color: #1d2939;
    cursor: pointer;
    background: #fff;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
}
.ot-col-chip:hover { border-color: #9cc2ee; background: #f7faff; }
.ot-col-chip .form-check-input {
    width: 20px;
    height: 20px;
    margin: 0;
    flex-shrink: 0;
    border-color: #98a2b3;
    border-radius: 5px;
    cursor: pointer;
}
.ot-col-chip .form-check-input:checked {
    background-color: #2f6fed;
    border-color: #2f6fed;
}
.ot-col-chip.is-checked { border-color: #9cc2ee; background: #f7faff; }
.ot-modal-close-btn {
    border: 1px solid #2f6fed;
    color: #2f6fed;
    background: #fff;
    font-weight: 600;
    font-size: 15px;
    padding: 10px 26px;
    border-radius: 12px;
    transition: background .15s ease, color .15s ease;
}
.ot-modal-close-btn:hover { background: #2f6fed; color: #fff; }

@media (max-width: 767.98px) {
    .ot-col-grid { grid-template-columns: repeat(2, 1fr); gap: 12px; }
}
@media (max-width: 480px) {
    .ot-col-grid { grid-template-columns: 1fr; }
}

/* ===== Table ===== */
#otDirectoryTable {
    border-collapse: separate;
    border-spacing: 0;
}
#otDirectoryTable thead th {
    font-size: 13px;
    font-weight: 500;
    color: #98a2b3;
    text-transform: none;
    letter-spacing: normal;
    padding: 14px 20px;
    border-bottom: 1px solid #eef0f3;
    background: #fcfcfd;
    white-space: nowrap;
    vertical-align: middle;
}
#otDirectoryTable tbody td {
    font-size: 14px;
    color: #475467;
    padding: 16px 20px;
    border-bottom: 1px solid #f2f4f7;
    vertical-align: middle;
}
#otDirectoryTable tbody tr:last-child td { border-bottom: none; }
#otDirectoryTable tbody tr { transition: background-color .15s ease; }
#otDirectoryTable tbody tr:hover { background: #f9fafb; }
.ot-col-sno { width: 70px; color: #667085; }
.ot-course-cell {
    max-width: 300px;
    white-space: normal;
    line-height: 1.5;
}

/* Name cell + avatar */
.ot-name-cell {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 190px;
}
.ot-avatar-photo,
.ot-avatar-letter {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    flex-shrink: 0;
}
.ot-avatar-photo {
    object-fit: cover;
    border: 1px solid #eef0f3;
}
.ot-avatar-letter {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 15px;
    color: #3f51b5;
    background: #e8eaf6;
    text-transform: uppercase;
}
.ot-student-name {
    font-weight: 500;
    color: #101828;
    white-space: nowrap;
}

/* ===== DataTables wrapper / footer / pagination ===== */
/* Remove the empty top toolbar row the global init injects (search is hidden) */
#otDirectoryTable_wrapper > .row:first-child { display: none !important; }
/* Hide the built-in DataTables search; server-side search is used instead */
.dataTables_wrapper .dataTables_filter { display: none !important; }

#otDirectoryTable_wrapper .dt-bottom-bar {
    border-top: 1px solid #eef0f3;
    margin-top: 0 !important;
    padding: 14px 20px;
}
.dataTables_wrapper .dataTables_paginate,
.dataTables_wrapper .dataTables_paginate .paginate_button,
.dataTables_wrapper .dataTables_paginate .page-item,
.dataTables_wrapper .dataTables_paginate .page-link {
    transition: none !important;
}
.dataTables_wrapper .dataTables_paginate { margin: 0 !important; padding: 0 !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 6px 12px !important;
    margin: 0 3px !important;
    border: 1px solid transparent !important;
    border-radius: 8px !important;
    font-size: 13px !important;
    color: #667085 !important;
    background: transparent !important;
    min-width: 34px;
    text-align: center;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    border: 1px solid #2f6fed !important;
    color: #2f6fed !important;
    font-weight: 600 !important;
    background: #fff !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
    background: #f2f4f7 !important;
    color: #344054 !important;
    border-color: #eaecf0 !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled:hover {
    color: #c3c9d2 !important;
    background: transparent !important;
    border-color: transparent !important;
}
.dataTables_wrapper .dataTables_length { margin: 0 !important; }
.dataTables_wrapper .dataTables_length select {
    border: 1px solid #d8dde5;
    border-radius: 8px;
    padding: 5px 10px;
    font-size: 13px;
    margin: 0 6px;
    color: #344054;
}
.dataTables_wrapper .dataTables_info,
.dataTables_wrapper .dt-showing-label {
    font-size: 13px;
    color: #667085;
    font-weight: 500;
}

/* ===== Responsive ===== */
@media (max-width: 991.98px) {
    .ot-toolbar .ms-auto { width: 100%; justify-content: flex-start; margin-left: 0 !important; }
    .ot-select { max-width: none; flex-grow: 1; }
}
@media (max-width: 767.98px) {
    .ot-directory-scroll {
        max-height: 70vh;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
    }
    #otDirectoryTable thead th { padding: 12px 14px; font-size: 12px; }
    #otDirectoryTable tbody td { padding: 12px 14px; font-size: 12.5px; }
    .ot-avatar-photo, .ot-avatar-letter { width: 34px; height: 34px; font-size: 13px; }
    .ot-search-wrap.expanded .ot-search-field { width: 150px; }
}
</style>
@endpush

@push('scripts')
<script>
(function () {
    function onReady(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }

    onReady(function () {
        var $ = window.jQuery;

        /* --- Expandable search --- */
        var wrap = document.getElementById('otSearchWrap');
        var toggle = document.getElementById('otSearchToggle');
        var input = document.getElementById('otSearchInput');
        if (toggle && wrap) {
            toggle.addEventListener('click', function () {
                // If expanded and has text, submit; otherwise just expand/focus.
                if (wrap.classList.contains('expanded') && input && input.value.trim() !== '') {
                    document.getElementById('otFilterForm').submit();
                    return;
                }
                wrap.classList.toggle('expanded');
                if (wrap.classList.contains('expanded') && input) { input.focus(); }
            });
        }

        /* --- Column Visibility modal (wired to the DataTable) --- */
        if (!$ || !$.fn || !$.fn.dataTable) return;
        var grid = document.getElementById('otColumnsGrid');
        var modalEl = document.getElementById('otColumnsModal');
        var tableEl = document.getElementById('otDirectoryTable');

        function buildColumnsGrid() {
            if (!grid || !tableEl) return false;
            if (!$.fn.dataTable.isDataTable(tableEl)) return false;
            if (grid.getAttribute('data-built')) return true;

            var dt = $(tableEl).DataTable();
            dt.columns().every(function () {
                var idx = this.index();
                var title = $(this.header()).text().trim() || ('Column ' + (idx + 1));
                var checked = this.visible();
                var label = document.createElement('label');
                label.className = 'ot-col-chip' + (checked ? ' is-checked' : '');
                label.innerHTML =
                    '<input type="checkbox" class="form-check-input" data-col="' + idx + '" ' +
                        (checked ? 'checked' : '') + '>' +
                    '<span>' + title + '</span>';
                grid.appendChild(label);
            });

            grid.addEventListener('change', function (e) {
                var cb = e.target;
                if (cb && cb.matches && cb.matches('input[data-col]')) {
                    dt.column(parseInt(cb.getAttribute('data-col'), 10)).visible(cb.checked);
                    cb.closest('.ot-col-chip').classList.toggle('is-checked', cb.checked);
                }
            });

            grid.setAttribute('data-built', '1');
            return true;
        }

        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', buildColumnsGrid);
        }
        // Fallback in case the table initialises after this script.
        setTimeout(buildColumnsGrid, 350);
    });
})();
</script>
@endpush

@endsection
