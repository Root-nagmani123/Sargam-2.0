@extends('admin.layouts.master')

@section('title', 'LBSNAA Directory - Sargam')

@section('content')
<div class="container-fluid">
    <x-breadcrum title="LBSNAA Directory"></x-breadcrum>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.directory.lbsnaa') }}" class="mb-3">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="form-label mb-1 fw-semibold">Search</label>
                        <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Name, email, mobile, designation">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1 fw-semibold">Sort by</label>
                        <select name="sort" class="form-select">
                            <option value="name_asc" {{ ($sort ?? 'name_asc') === 'name_asc' ? 'selected' : '' }}>Name (A-Z)</option>
                            <option value="name_desc" {{ ($sort ?? 'name_asc') === 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
                            <option value="designation_asc" {{ ($sort ?? 'name_asc') === 'designation_asc' ? 'selected' : '' }}>Designation (A-Z)</option>
                            <option value="designation_desc" {{ ($sort ?? 'name_asc') === 'designation_desc' ? 'selected' : '' }}>Designation (Z-A)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 fw-semibold">Per page</label>
                        <select name="per_page" class="form-select">
                            <option value="25" {{ (int) ($perPage ?? 50) === 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ (int) ($perPage ?? 50) === 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ (int) ($perPage ?? 50) === 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2 pt-md-4">
                        <button type="submit" class="btn btn-primary">Apply</button>
                        <a href="{{ route('admin.directory.lbsnaa') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button type="submit" name="export" value="csv" class="btn btn-outline-success btn-sm">Export CSV</button>
                    <button type="submit" name="export" value="excel" class="btn btn-outline-success btn-sm">Export Excel</button>
                </div>
            </form>
            @if($employees->total() > 0)
                <p class="mb-2 text-muted small">
                    Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} records
                </p>
            @endif
            <div class="table-responsive lbsnaa-directory-scroll">
                <table class="table align-middle" id="lbsnaaDirectoryTable">
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
            <div class="mt-3">
                {{ $employees->links() }}
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
    }
    #lbsnaaDirectoryTable tbody tr:nth-child(odd) {
        background: #ecebff;
    }
    .directory-photo {
        width: 56px;
        height: 56px;
        object-fit: cover;
        border-radius: 6px;
    }
</style>
@endpush

@endsection

