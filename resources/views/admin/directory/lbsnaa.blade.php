@extends('admin.layouts.master')

@section('title', 'LBSNAA Directory - Sargam')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="LBSNAA Directory"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.directory.lbsnaa') }}" class="mb-3">
                <div class="border rounded-3 p-3 bg-white">
                    <div class="row g-2 align-items-end lbsnaa-toolbar-row">
                        <div class="col-12 col-lg-6">
                            <label for="lbsnaaSearchInput" class="form-label mb-1 fw-semibold">Search</label>
                            <input
                                id="lbsnaaSearchInput"
                                type="text"
                                name="search"
                                value="{{ $search ?? '' }}"
                                class="form-control"
                                placeholder="Name, email, mobile, designation"
                                autocomplete="off"
                            >
                        </div>
                        <div class="col-6 col-lg-2 d-grid">
                            <button type="submit" class="btn btn-primary">Apply</button>
                        </div>
                        <div class="col-6 col-lg-1 d-grid">
                            <a href="{{ route('admin.directory.lbsnaa') }}" class="btn btn-outline-secondary">Reset</a>
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
            <div class="table-responsive lbsnaa-directory-scroll">
                <table class="table align-middle datatable" id="lbsnaaDirectoryTable" data-export="false" data-responsive="false">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Name</th>
                            <th>Designation Name</th>
                            <th>Section Name</th>
                            <th>Address</th>
                            <th>Office Extension</th>
                            <th>Mobile No.</th>
                            <th>Residence No.</th>
                            <th>Email ID</th>
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
                                <td>{{ ($employees->firstItem() ?? 0) + $index }}</td>
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
    .lbsnaa-toolbar-row .btn {
        white-space: nowrap;
    }
    @media (min-width: 992px) {
        .lbsnaa-toolbar-row {
            flex-wrap: nowrap;
        }
        .lbsnaa-toolbar-row > [class*="col-lg-"] {
            min-width: 0;
        }
    }
    @media (max-width: 991.98px) {
        .lbsnaa-toolbar-row .btn {
            width: 100%;
        }
    }
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
    }
    .directory-photo {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 6px;
    }
    @media (max-width: 768px) {
        .lbsnaa-directory-scroll,
        .lbsnaa-directory-scroll.table-responsive {
            max-height: 65vh;
            overflow-y: auto !important;
            overflow-x: hidden !important;
            -webkit-overflow-scrolling: touch;
        }
        #lbsnaaDirectoryTable {
            table-layout: fixed !important;
            width: 100% !important;
            margin-bottom: 0 !important;
        }
        #lbsnaaDirectoryTable thead th,
        #lbsnaaDirectoryTable tbody td {
            white-space: normal !important;
            word-break: break-word !important;
            overflow-wrap: anywhere !important;
            hyphens: auto;
            font-size: 11px;
            padding: 0.45rem;
        }
    }
</style>
@endpush

@endsection

