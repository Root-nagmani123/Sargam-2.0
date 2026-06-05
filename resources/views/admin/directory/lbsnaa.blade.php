@extends('admin.layouts.master')

@section('title', 'LBSNAA Directory')

@section('content')
<div class="container-fluid py-3">
    <x-breadcrum title="LBSNAA Directory"></x-breadcrum>

    {{-- Top bar: Download --}}
    <div class="d-flex align-items-center justify-content-end mb-4">
        <div class="dropdown">
            <button class="lbs-download-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M8 12L3 7h3.5V2h3V7H13L8 12z" fill="currentColor" />
                    <path d="M2 13h12v1.5H2V13z" fill="currentColor" />
                </svg>
                Download
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <form method="GET" action="{{ route('admin.directory.lbsnaa') }}" class="d-inline">
                        <button type="submit" name="export" value="csv" class="dropdown-item">CSV</button>
                    </form>
                </li>
                <li>
                    <form method="GET" action="{{ route('admin.directory.lbsnaa') }}" class="d-inline">
                        <button type="submit" name="export" value="excel" class="dropdown-item">Excel</button>
                    </form>
                </li>
            </ul>
        </div>
    </div>

    {{-- Directory table --}}
    <div class="lbs-table-card">
        {{-- Filter row --}}
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center gap-3">
                <span class="lbs-filter-label">Filters</span>
                <select id="designationFilter" class="lbs-filter-select">
                    <option value="">Designation</option>
                </select>
                <select id="sectionFilter" class="lbs-filter-select">
                    <option value="">Section</option>
                </select>
                <button type="button" id="clearSearch" class="lbs-reset-link">Reset Filters</button>
            </div>

            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="lbs-columns-btn dropdown-toggle" type="button" id="columnsDropdown"
                        data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        Columns
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"
                            style="margin-left:4px;">
                            <rect x="1" y="2" width="4" height="12" rx="1" stroke="currentColor" stroke-width="1.2"
                                fill="none" />
                            <rect x="6" y="2" width="4" height="12" rx="1" stroke="currentColor" stroke-width="1.2"
                                fill="none" />
                            <rect x="11" y="2" width="4" height="12" rx="1" stroke="currentColor" stroke-width="1.2"
                                fill="none" />
                        </svg>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 180px;">
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="0" checked> S.No.</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="1" checked> Name</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="2" checked> Designation</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="3" checked> Section</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="4" checked> Address</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="5" checked> Office Ext.</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="6" checked> Contact Number</label>
                        </li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="7" checked> Residence</label></li>
                        <li><label class="dropdown-item py-1 d-flex align-items-center gap-2"><input type="checkbox"
                                    class="form-check-input col-toggle" data-col="8" checked> Email</label></li>
                    </ul>
                </div>
                <input id="quickSearch" type="text" class="lbs-search-input" placeholder="Search..." autocomplete="off">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table mb-0" id="lbsnaaDirectoryTable" data-sargam-dt-ui="false">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>Section</th>
                        <th>Address</th>
                        <th>Office Ext.</th>
                        <th>Contact Number</th>
                        <th>Residence</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($employees as $index => $employee)
                    @php
                    $name = trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' .
                    ($employee->last_name ?? ''));
                    $email = $employee->officalemail ?: $employee->email;
                    $initial = strtoupper(substr($name ?: '-', 0, 1));
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="lbs-name-cell">
                                <div class="lbs-avatar-wrap">
                                    <span class="lbs-avatar-letter">{{ $initial }}</span>
                                    @if(!empty($employee->profile_picture))
                                    <img src="{{ asset('storage/' . $employee->profile_picture) }}" alt=""
                                        loading="lazy" decoding="async" onload="this.style.opacity='1';"
                                        onerror="this.remove();" class="lbs-avatar-img">
                                    @endif
                                </div>
                                <span class="lbs-emp-name">{{ $name ?: '-' }}</span>
                            </div>
                        </td>
                        <td>{{ $employee->designation_name ?: '-' }}</td>
                        <td>{{ $employee->department_name ?: '-' }}</td>
                        <td>{{ $employee->current_address ?: '-' }}</td>
                        <td>{{ $employee->office_extension_no ?: '-' }}</td>
                        <td>{{ $employee->mobile ?: '-' }}</td>
                        <td>{{ $employee->residence_no ?: '-' }}</td>
                        <td>{{ $email ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('styles')
<style>
/* ===== DOWNLOAD BUTTON ===== */
.lbs-download-btn {
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
    transition: background 0.15s;
}

.lbs-download-btn:hover {
    background: #e3f2fd;
}

.lbs-download-btn::after {
    display: none;
}

/* ===== FILTER ROW ===== */
.lbs-filter-label {
    font-size: 14px;
    font-weight: 500;
    color: #666;
}

.lbs-filter-select {
    appearance: none;
    padding: 7px 32px 7px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath d='M3 5l3 3 3-3' stroke='%23666' stroke-width='1.5' fill='none'/%3E%3C/svg%3E") no-repeat right 10px center;
    cursor: pointer;
    min-width: 150px;
    outline: none;
    transition: border-color 0.15s;
}

.lbs-filter-select:focus {
    border-color: #1565c0;
}

.lbs-reset-link {
    font-size: 14px;
    font-weight: 500;
    color: #d32f2f;
    text-decoration: none;
    border: 1.5px solid #d32f2f;
    padding: 6px 14px;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
    transition: background 0.15s;
}

.lbs-reset-link:hover {
    background: #ffebee;
}

/* ===== COLUMNS BUTTON ===== */
.lbs-columns-btn {
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
    transition: border-color 0.15s;
}

.lbs-columns-btn:hover {
    border-color: #999;
}

.lbs-columns-btn::after {
    display: none;
}



/* ===== TABLE CARD ===== */
.lbs-table-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e8e8e8;
    overflow: hidden;
    padding: 24px;
}

/* ===== TABLE ===== */
#lbsnaaDirectoryTable thead th {
    font-size: 12px;
    font-weight: 500;
    color: #888;
    text-transform: none;
    padding: 14px 16px;
    border-bottom: 1px solid #eee;
    background: #fafafa;
    white-space: nowrap;
}

#lbsnaaDirectoryTable tbody td {
    font-size: 14px;
    color: #333;
    padding: 14px 16px;
    border-bottom: 1px solid #f7f7f7;
    vertical-align: middle;
}

#lbsnaaDirectoryTable tbody tr:last-child td {
    border-bottom: none;
}

#lbsnaaDirectoryTable tbody tr:hover {
    background: #f8f9fa;
}

/* ===== NAME CELL ===== */
.lbs-name-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.lbs-avatar-wrap {
    width: 40px;
    height: 40px;
    position: relative;
    flex-shrink: 0;
    border-radius: 50%;
    overflow: hidden;
}

.lbs-avatar-img {
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

.lbs-avatar-letter {
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

.lbs-emp-name {
    font-weight: 400;
    white-space: nowrap;
}

/* ===== PAGINATION ===== */
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

.dataTables_wrapper .dataTables_filter {
    display: none !important;
}

/* ===== PAGINATION FOOTER ===== */
.lbs-pagination-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-top: 1px solid #eee;
}

.lbs-pag-left {
    display: flex;
    align-items: center;
}

.lbs-pag-right {
    display: flex;
    align-items: center;
    gap: 4px;
}

.lbs-search-input {
    padding: 7px 12px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    color: #333;
    width: 160px;
    outline: none;
    transition: border-color 0.15s;
}

.lbs-search-input:focus {
    border-color: #1565c0;
}

@media (max-width: 768px) {
    .lbs-filter-select {
        min-width: 120px;
    }

    .lbs-search-input {
        width: 120px;
    }
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#lbsnaaDirectoryTable').DataTable({
        responsive: false,
        pageLength: 10,
        lengthMenu: [10, 20, 50, 100, 200, 500, "All"],
        order: [
            [1, 'asc']
        ],
        dom: 't<"lbs-pagination-footer"<"lbs-pag-left"p><"lbs-pag-right"l i>>',
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
        search: {
            smart: false
        },
        columnDefs: [{
            orderable: false,
            searchable: false,
            targets: [0]
        }]
    });

    // Populate Designation filter from column 2
    var designations = [];
    table.column(2).data().each(function(val) {
        if (val && val !== '-' && designations.indexOf(val) === -1) designations.push(val);
    });
    designations.sort();
    designations.forEach(function(d) {
        $('#designationFilter').append('<option value="' + d + '">' + d + '</option>');
    });

    // Populate Section filter from column 3
    var sections = [];
    table.column(3).data().each(function(val) {
        if (val && val !== '-' && sections.indexOf(val) === -1) sections.push(val);
    });
    sections.sort();
    sections.forEach(function(s) {
        $('#sectionFilter').append('<option value="' + s + '">' + s + '</option>');
    });

    // Designation filter
    $('#designationFilter').on('change', function() {
        var val = $(this).val();
        table.column(2).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false)
            .draw();
    });

    // Section filter
    $('#sectionFilter').on('change', function() {
        var val = $(this).val();
        table.column(3).search(val ? '^' + $.fn.dataTable.util.escapeRegex(val) + '$' : '', true, false)
            .draw();
    });

    // Quick search functionality
    $('#quickSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Clear/Reset all filters
    $('#clearSearch').on('click', function() {
        $('#quickSearch').val('');
        $('#designationFilter').val('');
        $('#sectionFilter').val('');
        table.search('').columns().search('').draw();
    });

    // Column toggle
    $('.col-toggle').on('change', function() {
        var colIdx = parseInt($(this).data('col'));
        var column = table.column(colIdx);
        column.visible($(this).is(':checked'));
    });
});
</script>
@endpush

@endsection