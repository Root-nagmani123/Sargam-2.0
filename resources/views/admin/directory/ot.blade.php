@extends('admin.layouts.master')

@section('title', 'OT Directory')

@section('content')
<div class="container-fluid py-3">
    <x-breadcrum title="OT Directory"></x-breadcrum>

    {{-- Top bar: Active/Archived + Download --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div class="ot-tab-group">
            <button type="button" class="ot-tab {{ ($status ?? 'active') === 'active' ? 'active' : '' }}" data-status="active" onclick="switchStatus('active')">Active</button>
            <button type="button" class="ot-tab {{ ($status ?? 'active') === 'archived' ? 'active' : '' }}" data-status="archived" onclick="switchStatus('archived')">Archived</button>
        </div>
        
        <div class="dropdown">
            <button class="ot-download-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 12L3 7h3.5V2h3V7H13L8 12z" fill="currentColor"/>
                    <path d="M2 13h12v1.5H2V13z" fill="currentColor"/>
                </svg>
                Download
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="#" onclick="exportToCSV(); return false;">CSV</a></li>
                <li><a class="dropdown-item" href="#" onclick="exportToExcel(); return false;">Excel</a></li>
            </ul>
        </div>
    </div>

    {{-- Directory table --}}
    <div class="ot-table-card">
        {{-- Filter row --}}
    <form method="GET" action="{{ route('admin.directory.ot') }}" id="otFilterForm">
        <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-3">
                <span class="ot-filter-label">Filters</span>
                <div class="ot-select-wrap">
                    <select name="course_id" class="ot-select" id="otCourseSelect">
                        <option value="">Program Name</option>
                        @foreach($activeCourses as $course)
                        <option value="{{ $course->pk }}" {{ (int) $selectedCourseId === (int) $course->pk ? 'selected' : '' }}>
                            {{ $course->couse_short_name ?: $course->course_name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <a href="{{ route('admin.directory.ot', ['status' => $status ?? 'active']) }}" class="ot-reset-link">Reset Filters</a>
            </div>
            
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="ot-columns-btn dropdown-toggle" type="button" id="columnsDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        Columns
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-left:4px;">
                            <rect x="1" y="2" width="4" height="12" rx="1" stroke="currentColor" stroke-width="1.2" fill="none"/>
                            <rect x="6" y="2" width="4" height="12" rx="1" stroke="currentColor" stroke-width="1.2" fill="none"/>
                            <rect x="11" y="2" width="4" height="12" rx="1" stroke="currentColor" stroke-width="1.2" fill="none"/>
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 180px;">
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="0" checked> S. No.</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="1" checked> Name</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="2" checked> OT Code</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="3" checked> Room No.</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="4" checked> Room Extension No.</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="5" checked> Email ID</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="6" checked> Course Name ID</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox" class="form-check-input col-toggle" data-col="7" checked> Cadre Name</label></li>
                    </ul>
                </div>
                <button type="button" class="ot-search-btn" onclick="toggleSearch()">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="7" cy="7" r="5" stroke="currentColor" stroke-width="1.5" fill="none"/>
                        <line x1="11" y1="11" x2="14" y2="14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>
        </div>
    </form>

    {{-- Search bar (hidden by default) --}}
    <div id="otSearchWrap" class="mb-3 d-none">
        <input type="text" id="otSearchInput" class="form-control" placeholder="Search by name, OT code, email..." onkeyup="filterTable(this.value)">
    </div>
        <div class="table-responsive">
            <table class="table mb-0" id="otDirectoryTable" data-sargam-dt-ui="false">
                <thead>
                    <tr>
                        <th>S. No.</th>
                        <th>Name</th>
                        <th>OT Code</th>
                        <th>Room No.</th>
                        <th>Room Extension No.</th>
                        <th>Email ID</th>
                        <th>Course Name ID</th>
                        <th>Cadre Name</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($students as $index => $student)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="ot-name-cell">
                                <div class="ot-avatar-wrap">
                                    <span class="ot-avatar-letter">{{ strtoupper(substr($student->display_name ?? '-', 0, 1)) }}</span>
                                    @if(!empty($student->photo_path))
                                    <img src="{{ asset('storage/' . $student->photo_path) }}" alt=""
                                        loading="lazy" decoding="async"
                                        onload="this.style.opacity='1';"
                                        onerror="this.remove();"
                                        class="ot-avatar-img">
                                    @endif
                                </div>
                                <span class="ot-student-name">{{ $student->display_name ?: '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $student->generated_OT_code ?: '-' }}</td>
                        <td>{{ $student->hostel_room_name ?: '-' }}</td>
                        <td>-</td>
                        <td>{{ $student->email ?: '-' }}</td>
                        <td>{{ $student->course_name ?: '-' }}</td>
                        <td>{{ $student->cadre_name ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">No records found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
/* ===== TOP TAB GROUP ===== */
.ot-tab-group {
    display: inline-flex;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    background: #fff;
}
.ot-tab {
    padding: 8px 24px;
    border: none;
    background: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    color: #333;
    transition: all 0.15s;
    line-height: 1.4;
}
.ot-tab.active {
    background: #1a237e;
    color: #fff;
}
.ot-tab:hover:not(.active) {
    background: #f5f5f5;
}

/* ===== DOWNLOAD BUTTON ===== */
.ot-download-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: 1.5px solid #1565c0;
    border-radius: 6px;
    background: #fff;
    color: #1565c0;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
}
.ot-download-btn:hover {
    background: #e3f2fd;
}
.ot-download-btn::after {
    display: none;
}

/* ===== FILTER ROW ===== */
.ot-filter-label {
    font-size: 14px;
    font-weight: 500;
    color: #666;
}
.ot-select-wrap {
    position: relative;
}
.ot-select {
    appearance: none;
    padding: 7px 32px 7px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M3 5l3 3 3-3' stroke='%23666' stroke-width='1.5' fill='none'/%3E%3C/svg%3E") no-repeat right 10px center;
    cursor: pointer;
    min-width: 160px;
}
.ot-select:focus {
    outline: none;
    border-color: #1565c0;
}
.ot-reset-link {
    font-size: 14px;
    font-weight: 500;
    color: #d32f2f;
    text-decoration: none;
    border: 1.5px solid #d32f2f;
    padding: 6px 14px;
    border-radius: 6px;
    transition: all 0.15s;
}
.ot-reset-link:hover {
    background: #ffebee;
    color: #c62828;
}

/* ===== COLUMNS BUTTON ===== */
.ot-columns-btn {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 7px 14px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    background: #fff;
    font-size: 14px;
    color: #333;
    cursor: pointer;
    transition: all 0.15s;
}
.ot-columns-btn:hover {
    border-color: #999;
}
.ot-columns-btn::after {
    display: none;
}

/* ===== SEARCH BUTTON ===== */
.ot-search-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    background: #fff;
    color: #555;
    cursor: pointer;
    transition: all 0.15s;
}
.ot-search-btn:hover {
    border-color: #999;
    background: #f5f5f5;
}

/* ===== TABLE CARD ===== */
.ot-table-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e8e8e8;
    overflow: hidden;
    padding: 24px;
}

/* ===== TABLE ===== */
#otDirectoryTable {
    border-collapse: collapse;
}
#otDirectoryTable thead th {
    font-size: 12px;
    font-weight: 500;
    color: #888;
    text-transform: none;
    padding: 14px 16px;
    border-bottom: 1px solid #eee;
    background: #fafafa;
    white-space: nowrap;
}
#otDirectoryTable tbody td {
    font-size: 14px;
    color: #333;
    padding: 16px 16px;
    border-bottom: 1px solid #f7f7f7;
    vertical-align: middle;
}
#otDirectoryTable tbody tr:last-child td {
    border-bottom: none;
}
#otDirectoryTable tbody tr:hover {
    background: #f8f9fa;
}

/* ===== NAME CELL ===== */
.ot-name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}
.ot-avatar-wrap {
    width: 40px;
    height: 40px;
    position: relative;
    flex-shrink: 0;
    border-radius: 50%;
    overflow: hidden;
}
.ot-avatar-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
    opacity: 0;
    transition: opacity 0.15s;
}
.ot-avatar-letter {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e8eaf6;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    color: #3f51b5;
}
.ot-student-name {
    font-weight: 400;
    white-space: nowrap;
}

/* ===== PAGINATION BAR ===== */
.ot-pagination-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid #eee;
}
.ot-pag-left {
    display: flex;
    align-items: center;
}
.ot-pag-right {
    display: flex;
    align-items: center;
    gap: 4px;
}
/* Fix: Remove transitions from pagination to prevent click delays */
.dataTables_wrapper .dataTables_paginate .paginate_button,
.dataTables_wrapper .dataTables_paginate .page-item,
.dataTables_wrapper .dataTables_paginate .page-link {
    transition: none !important;
}
.dataTables_wrapper .dataTables_paginate {
    margin: 0 !important;
    padding: 0 !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 4px 10px !important;
    margin: 0 2px !important;
    border: 1px solid transparent !important;
    border-radius: 4px !important;
    font-size: 13px !important;
    color: #555 !important;
    background: transparent !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    border: 1px solid #1565c0 !important;
    color: #1565c0 !important;
    font-weight: 600 !important;
    background: transparent !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
    background: #f0f0f0 !important;
    color: #333 !important;
    border-color: #ddd !important;
}
.dataTables_wrapper .dataTables_length {
    margin: 0 !important;
}
.dataTables_wrapper .dataTables_length select {
    border: 1px solid #d0d0d0;
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 13px;
    margin: 0 4px;
}
.dataTables_wrapper .dataTables_info {
    font-size: 13px;
    color: #666;
    padding: 0 !important;
    margin: 0 !important;
}
/* Hide default DataTables search (we use our own) */
.dataTables_wrapper .dataTables_filter {
    display: none !important;
}
</style>
@endpush

@push('scripts')
<script>
var otTable;

$(document).ready(function() {
    otTable = $('#otDirectoryTable').DataTable({
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100, 200, 500],
        responsive: false,
        dom: 't<"ot-pagination-footer"<"ot-pag-left"p><"ot-pag-right"l i>>',
        language: {
            lengthMenu: 'Showing _MENU_',
            info: 'of _TOTAL_ items',
            infoEmpty: 'of 0 items',
            infoFiltered: '',
            paginate: {
                previous: '‹',
                next: '›'
            }
        },
        searching: true,
        ordering: true,
        search: { smart: false },
        columnDefs: [
            { orderable: false, targets: [0] }
        ]
    });

    // Auto-submit course filter on change
    $('#otCourseSelect').on('change', function() {
        $('#otFilterForm').submit();
    });

    // Column toggle
    $('.col-toggle').on('change', function() {
        var colIdx = parseInt($(this).data('col'));
        var column = otTable.column(colIdx);
        column.visible($(this).is(':checked'));
    });
});

function toggleSearch() {
    var wrap = document.getElementById('otSearchWrap');
    var input = document.getElementById('otSearchInput');
    if (wrap.classList.contains('d-none')) {
        wrap.classList.remove('d-none');
        input.focus();
    } else {
        wrap.classList.add('d-none');
        input.value = '';
        if (otTable) otTable.search('').draw();
    }
}

function filterTable(val) {
    if (otTable) otTable.search(val).draw();
}

function switchStatus(status) {
    document.querySelectorAll('.ot-tab').forEach(function(btn) { btn.classList.remove('active'); });
    document.querySelector('[data-status="' + status + '"]').classList.add('active');
    document.querySelector('input[name="status"]').value = status;
    document.getElementById('otFilterForm').submit();
}

function exportToCSV() {
    var csv = [];
    var headers = [];
    otTable.columns(':visible').every(function() { headers.push($(this.header()).text().trim()); });
    csv.push(headers.join(','));
    otTable.rows({search:'applied'}).every(function(rowIdx) {
        var cols = [];
        otTable.columns(':visible').every(function(colIdx) {
            var cellText = $(otTable.cell(rowIdx, colIdx).node()).text().trim();
            cols.push('"' + cellText.replace(/"/g, '""') + '"');
        });
        csv.push(cols.join(','));
    });
    downloadFile(csv.join('\n'), 'OT_Directory.csv', 'text/csv');
}

function exportToExcel() {
    var table = document.getElementById('otDirectoryTable');
    var html = table.outerHTML.replace(/<img[^>]*>/g, '');
    var tpl = '<html xmlns:x="urn:schemas-microsoft-com:office:excel"><head><meta charset="UTF-8"></head><body>' + html + '</body></html>';
    var b64 = window.btoa(unescape(encodeURIComponent(tpl)));
    var blob = new Blob([Uint8Array.from(atob(b64), c => c.charCodeAt(0))], {type:'application/vnd.ms-excel'});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url; a.download = 'OT_Directory.xls';
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
}

function downloadFile(content, filename, type) {
    var blob = new Blob([content], {type: type});
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
@endpush

@endsection
