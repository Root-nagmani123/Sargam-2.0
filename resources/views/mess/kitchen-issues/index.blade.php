@extends('admin.layouts.master')
@section('title', 'Kitchen Issues')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Kitchen Issues</h4>
        <a href="{{ route('admin.mess.kitchen-issues.create') }}" class="btn btn-primary">Create Kitchen Issue</a>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    
    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.mess.kitchen-issues.index') }}">
                <div class="row">
                    <div class="col-md-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                            <option value="issued" {{ request('status') == 'issued' ? 'selected' : '' }}>Issued</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Store</label>
                        <select name="store" class="form-select">
                            <option value="">All</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>
                                    {{ $store->store_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Start Date</label>
                        <input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label>End Date</label>
                        <input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}">
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Issue No</th>
                    <th>Request Date</th>
                    <th>Issue Date</th>
                    <th>Store</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($kitchenIssues as $issue)
                <tr>
                    <td>{{ $issue->pk }}</td>
                    <td>{{ $issue->request_date ? \Carbon\Carbon::parse($issue->request_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $issue->issue_date ? \Carbon\Carbon::parse($issue->issue_date)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $issue->storeMaster->store_name ?? 'N/A' }}</td>
                    <td>{{ $issue->quantity }}</td>
                    <td>
                        @if($issue->status == 'pending')
                            <span class="badge bg-warning">Pending</span>
                        @elseif($issue->status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($issue->status == 'issued')
                            <span class="badge bg-primary">Issued</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($issue->status) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($issue->payment_type == 1)
                            <span class="badge bg-success">Paid</span>
                        @else
                            <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.mess.kitchen-issues.show', $issue->pk) }}" class="btn btn-sm btn-info">View</a>
                        @if($issue->status == 'pending')
                            <a href="{{ route('admin.mess.kitchen-issues.edit', $issue->pk) }}" class="btn btn-sm btn-warning">Edit</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">No kitchen issues found</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="d-flex justify-content-center">
        {{ $kitchenIssues->links() }}
    </div>
</div>
@endsection
