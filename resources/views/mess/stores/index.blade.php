@extends('admin.layouts.master')
@section('title', 'Mess Stores')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Mess Stores</h4>
        <a href="{{ route('mess.stores.create') }}" class="btn btn-primary">Add Store</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Store Code</th>
                <th>Store Name</th>
                <th>Location</th>
                <th>Incharge</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($stores as $store)
            <tr>
                <td>{{ $store->store_code }}</td>
                <td>{{ $store->store_name }}</td>
                <td>{{ $store->location }}</td>
                <td>{{ $store->incharge_name }}</td>
                <td>{{ $store->incharge_contact }}</td>
                <td><span class="badge bg-{{ $store->status == 'active' ? 'success' : 'danger' }}">{{ $store->status }}</span></td>
                <td>
                    <a href="{{ route('mess.stores.edit', $store->id) }}" class="btn btn-sm btn-warning">Edit</a>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
