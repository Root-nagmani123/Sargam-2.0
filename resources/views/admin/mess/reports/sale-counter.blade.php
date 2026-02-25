@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:shop-bold" class="me-2"></iconify-icon>
            Sale Counter Report
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control form-control-sm" 
                           value="{{ request('from_date') }}" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control form-control-sm" 
                           value="{{ request('to_date') }}" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Counter Name</th>
                        <th>Store</th>
                        <th>Total Transactions</th>
                        <th>Total Sales</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($counters as $counter)
                        <tr>
                            <td>{{ $counter->counter_name }}</td>
                            <td>{{ $counter->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $counter->transactions->count() }}</td>
                            <td>₹{{ number_format($counter->transactions->sum('amount') ?? 0, 2) }}</td>
                            <td>
                                <span class="badge {{ $counter->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $counter->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">No sale counter data found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
