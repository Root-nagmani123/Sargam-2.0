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
    #lbsnaaDirectoryTable thead th {
        background: #2f7fc0;
        color: #fff;
        font-size: 12px;
        letter-spacing: 0.5px;
        white-space: nowrap;
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

