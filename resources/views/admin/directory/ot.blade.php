@extends('admin.layouts.master')

@section('title', 'OT Directory - Sargam')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="OT Directory"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.directory.ot') }}" class="mb-3">
                <div class="border rounded-3 p-3 bg-white">
                    <div class="row g-2 align-items-end ot-toolbar-row">
                        <div class="col-12 col-lg-3">
                            <label for="otCourseSelect" class="form-label mb-1 fw-semibold">Program Name*</label>
                            <select name="course_id" class="form-select" id="otCourseSelect">
                                <option value="">Select Program</option>
                                @foreach($activeCourses as $course)
                                    <option value="{{ $course->pk }}" {{ (int) $selectedCourseId === (int) $course->pk ? 'selected' : '' }}>
                                        {{ $course->couse_short_name ?: $course->course_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-4">
                            <label for="otSearchInput" class="form-label mb-1 fw-semibold">Search</label>
                            <input
                                id="otSearchInput"
                                type="text"
                                name="search"
                                value="{{ $search ?? '' }}"
                                class="form-control"
                                placeholder="Name, OT code, email, cadre"
                                autocomplete="off"
                            >
                        </div>

                        <div class="col-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>

                        <div class="col-6 col-lg-1 d-grid">
                            <a href="{{ route('admin.directory.ot', ['course_id' => $selectedCourseId]) }}" class="btn btn-outline-secondary">Reset</a>
                        </div>

                        <div class="col-6 col-lg-1 d-grid">
                            <button type="submit" name="export" value="csv" class="btn btn-outline-success btn-sm">CSV</button>
                        </div>

                        <div class="col-6 col-lg-1 d-grid">
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success btn-sm">Excel</button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="table-responsive ot-directory-scroll">
                <table class="table align-middle datatable" id="otDirectoryTable" data-export="false">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>OT Code</th>
                            <th>Room No.</th>
                            <th>Room Extension No.</th>
                            <th>Email ID</th>
                            <th>Course Name</th>
                            <th>Cadre Name</th>
                            <th>Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($students as $index => $student)
                            <tr>
                                <td>{{ ($students->firstItem() ?? 0) + $index }}</td>
                                <td>{{ $student->display_name ?: '-' }}</td>
                                <td>{{ $student->generated_OT_code ?: '-' }}</td>
                                <td>-</td>
                                <td>-</td>
                                <td>{{ $student->email ?: '-' }}</td>
                                <td>{{ $student->course_name ?: '-' }}</td>
                                <td>{{ $student->cadre_name ?: '-' }}</td>
                                <td>
                                    @if(!empty($student->photo_path))
                                        <img src="{{ asset('storage/' . $student->photo_path) }}" alt="photo" class="directory-photo" loading="lazy" decoding="async">
                                    @else
                                        <img src="{{ asset('images/dummypic.jpeg') }}" alt="photo" class="directory-photo" loading="lazy" decoding="async">
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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

@endsection

