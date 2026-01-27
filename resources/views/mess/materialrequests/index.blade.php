@extends('admin.layouts.master')
@section('title', 'Material Requests')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Material Requests</h4>
        <a href="{{ route('admin.mess.materialrequests.create') }}" class="btn btn-primary">Create Request</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Request #</th>
                <th>Date</th>
                <th>Store</th>
                <th>Requested By</th>
                <th>Status</th>
                <th>Items</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($requests as $request)
            <tr>
                <td>{{ $request->request_number }}</td>
                <td>{{ $request->request_date->format('d/m/Y') }}</td>
                <td>{{ $request->store->store_name ?? 'N/A' }}</td>
                <td>{{ $request->requester->name ?? 'N/A' }}</td>
                <td>
                    <span class="badge bg-{{ $request->status == 'approved' ? 'success' : ($request->status == 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($request->status) }}
                    </span>
                </td>
                <td>{{ $request->items->count() }} items</td>
                <td>
                    <a href="{{ route('admin.mess.materialrequests.show', $request->id) }}" class="btn btn-sm btn-info">View</a>
                    @if($request->status == 'pending')
                        <a href="{{ route('admin.mess.materialrequests.approve', $request->id) }}" class="btn btn-sm btn-success">Approve</a>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
