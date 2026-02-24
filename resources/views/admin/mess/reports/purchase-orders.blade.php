@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:cart-large-2-bold" class="me-2"></iconify-icon>
            Purchase Orders Report
        </h5>
    </div>
    <div class="card-body">
        <form method="GET" class="mb-3">
            <div class="row g-2">
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm select2">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Received</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="from_date" class="form-control form-control-sm" 
                           value="{{ request('from_date') }}" placeholder="From Date">
                </div>
                <div class="col-md-3">
                    <input type="date" name="to_date" class="form-control form-control-sm" 
                           value="{{ request('to_date') }}" placeholder="To Date">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <iconify-icon icon="solar:magnifer-bold"></iconify-icon> Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>PO Number</th>
                        <th>PO Date</th>
                        <th>Vendor</th>
                        <th>Store</th>
                        <th>Items Count</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr>
                            <td>{{ $order->po_number }}</td>
                            <td>{{ $order->po_date ? date('d-M-Y', strtotime($order->po_date)) : 'N/A' }}</td>
                            <td>{{ $order->vendor->vendor_name ?? 'N/A' }}</td>
                            <td>{{ $order->store->store_name ?? 'N/A' }}</td>
                            <td>{{ $order->items->count() }}</td>
                            <td>₹{{ number_format($order->total_amount ?? 0, 2) }}</td>
                            <td>
                                @php
                                    $statusColors = [
                                        'draft' => 'secondary',
                                        'pending' => 'warning',
                                        'approved' => 'success',
                                        'received' => 'info',
                                        'cancelled' => 'danger'
                                    ];
                                    $color = $statusColors[$order->status ?? 'draft'] ?? 'secondary';
                                @endphp
                                <span class="badge bg-{{ $color }}">
                                    {{ ucfirst($order->status ?? 'draft') }}
                                </span>
                            </td>
                            <td>{{ $order->createdBy->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No purchase orders found</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($orders->count() > 0)
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="5" class="text-end">Total Amount (Page):</th>
                            <th colspan="3">₹{{ number_format($orders->sum('total_amount'), 2) }}</th>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $orders->withQueryString()->links() }}
        </div>
    </div>
</div>
@endsection
