@extends('admin.layouts.master')
@section('title', 'Mess Stores')
@section('setup_content')
@php
    $storeTypes = \App\Models\Mess\Store::storeTypes();
@endphp
<div class="container-fluid">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Store Master</h4>
                <a href="{{ route('admin.mess.stores.create') }}" class="btn btn-primary">Add Store</a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 70px; background-color: #af2910; color: #fff;">#</th>
                            <th style="background-color: #af2910; color: #fff;">Store Name</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Store Type</th>
                            <th style="background-color: #af2910; color: #fff;">Location</th>
                            <th style="width: 120px; background-color: #af2910; color: #fff;">Status</th>
                            <th style="width: 160px; background-color: #af2910; color: #fff;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stores as $store)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $store->store_name }}</div>
                                    <div class="text-muted small">Code: {{ $store->store_code }}</div>
                                </td>
                                <td class="text-capitalize">{{ $store->store_type }}</td>
                                <td>{{ $store->location ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $store->status_badge_class }}">
                                        {{ $store->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.mess.stores.edit', $store->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                        <form method="POST" action="{{ route('admin.mess.stores.destroy', $store->id) }}"
                                              onsubmit="return confirm('Are you sure you want to delete this store?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No stores found.</td>
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
