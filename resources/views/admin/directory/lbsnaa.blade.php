@extends('admin.layouts.master')

@section('title', 'LBSNAA Directory - Sargam')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="LBSNAA Directory"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="border rounded-3 p-3 bg-white mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="quickSearch" class="form-label mb-1 fw-semibold">Quick Search</label>
                        <input
                            id="quickSearch"
                            type="text"
                            class="form-control"
                            placeholder="Search name, email, mobile, designation..."
                            autocomplete="off"
                        >
                    </div>
                    <div class="col-6 col-md-3 col-lg-2">
                        <button type="button" id="clearSearch" class="btn btn-outline-secondary w-100">
                            <i class="ti ti-refresh"></i> Clear
                        </button>
                    </div>
                    <div class="col-auto ms-auto">
                        <form method="GET" action="{{ route('admin.directory.lbsnaa') }}" class="d-inline">
                            <button type="submit" name="export" value="csv" class="btn btn-outline-success btn-sm">
                                <i class="ti ti-file-spreadsheet"></i> CSV
                            </button>
                            <button type="submit" name="export" value="excel" class="btn btn-outline-success btn-sm ms-1">
                                <i class="ti ti-file-excel"></i> Excel
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="table-responsive lbsnaa-table-wrapper">
                <table class="table align-middle" id="lbsnaaDirectoryTable">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>Designation</th>
                            <th>Section</th>
                            <th>Address</th>
                            <th>Office Ext.</th>
                            <th>Mobile</th>
                            <th>Residence</th>
                            <th>Email</th>
                            <th>Photo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($employees as $index => $employee)
                            @php
                                $name = trim(($employee->first_name ?? '') . ' ' . ($employee->middle_name ?? '') . ' ' . ($employee->last_name ?? ''));
                                $email = $employee->officalemail ?: $employee->email;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $name ?: '-' }}</td>
                                <td>{{ $employee->designation_name ?: '-' }}</td>
                                <td>{{ $employee->department_name ?: '-' }}</td>
                                <td>{{ $employee->current_address ?: '-' }}</td>
                                <td>{{ $employee->office_extension_no ?: '-' }}</td>
                                <td>{{ $employee->mobile ?: '-' }}</td>
                                <td>{{ $employee->residence_no ?: '-' }}</td>
                                <td>{{ $email ?: '-' }}</td>
                                <td>
                                    @if(!empty($employee->profile_picture))
                                        <img src="{{ asset('storage/' . $employee->profile_picture) }}" alt="photo" class="directory-photo" loading="lazy" decoding="async">
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
    #lbsnaaDirectoryTable tbody td {
        font-size: 12px;
        vertical-align: middle;
        white-space: nowrap;
    }
    .directory-photo {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 6px;
    }
    div.dataTables_wrapper div.dataTables_length select {
        width: auto;
        display: inline-block;
    }
    
    /* Responsive scroll for small screens */
    @media (max-width: 768px) {
        .lbsnaa-table-wrapper {
            max-height: 70vh;
            overflow-x: auto !important;
            overflow-y: auto !important;
            -webkit-overflow-scrolling: touch;
        }
        
        #lbsnaaDirectoryTable {
            min-width: 1200px;
            margin-bottom: 0;
        }
        
        #lbsnaaDirectoryTable thead th,
        #lbsnaaDirectoryTable tbody td {
            font-size: 11px;
            padding: 0.5rem;
        }
        
        .directory-photo {
            width: 48px;
            height: 48px;
        }
    }
    
    @media (max-width: 576px) {
        #lbsnaaDirectoryTable thead th,
        #lbsnaaDirectoryTable tbody td {
            font-size: 10px;
            padding: 0.4rem;
        }
        
        .directory-photo {
            width: 40px;
            height: 40px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    var table = $('#lbsnaaDirectoryTable').DataTable({
        responsive: false,
        scrollX: true,
        scrollCollapse: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[1, 'asc']], // Sort by Name
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries",
            infoEmpty: "Showing 0 to 0 of 0 entries",
            infoFiltered: "(filtered from _MAX_ total entries)",
            paginate: {
                first: "First",
                last: "Last",
                next: "Next",
                previous: "Previous"
            }
        },
        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rtip',
        columnDefs: [
            {
                targets: 0, // S.No. column
                orderable: true,
                searchable: false
            },
            {
                targets: 9, // Photo column
                orderable: false,
                searchable: false
            }
        ]
    });

    // Quick search functionality
    $('#quickSearch').on('keyup', function() {
        table.search(this.value).draw();
    });

    // Clear search button
    $('#clearSearch').on('click', function() {
        $('#quickSearch').val('');
        table.search('').draw();
    });
});
</script>
@endpush

@endsection

