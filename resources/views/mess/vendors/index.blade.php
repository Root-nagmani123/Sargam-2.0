@extends('admin.layouts.master')
@section('title', 'Mess Vendors')
@section('setup_content')
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Vendor Master</h4>
                <a href="{{ route('admin.mess.vendors.create') }}" class="btn btn-primary">Add Vendor</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff;">#</th>
                            <th style="background-color: #af2910; color: #fff;">Vendor Name</th>
                            <th style="background-color: #af2910; color: #fff;">Email</th>
                            <th style="background-color: #af2910; color: #fff;">Contact Person</th>
                            <th style="background-color: #af2910; color: #fff;">Phone</th>
                            <th style="background-color: #af2910; color: #fff;">Address</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($vendors as $vendor)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $vendor->name }}</div>
                                </td>
                                <td>{{ $vendor->email ?? '-' }}</td>
                                <td>{{ $vendor->contact_person ?? '-' }}</td>
                                <td>{{ $vendor->phone ?? '-' }}</td>
                                <td>{{ $vendor->address ?? '-' }}</td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.mess.vendors.edit', $vendor->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="{{ route('admin.mess.vendors.destroy', $vendor->id) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this vendor?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No vendors found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
    .table thead th {
        background-color: #af2910 !important;
        color: #fff !important;
    }
</style>
@endsection
