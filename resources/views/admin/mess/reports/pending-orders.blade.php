@extends('admin.layouts.master')

@section('setup_content')
<div class="card" style="border-left: 4px solid #004a93;">
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:cart-check-bold" class="me-2"></iconify-icon>
            Pending Purchase Orders
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover table-sm">
                <thead class="table-light">
                    <tr>
                        <th>PO Number</th>
                        <th>PO Date</th>
                        <th>Vendor</th>
                        <th>Store</th>
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
                            <td>₹{{ number_format($order->total_amount ?? 0, 2) }}</td>
                            <td>
                                <span class="badge {{ $order->status == 'approved' ? 'bg-success' : 'bg-warning' }}">
                                    {{ ucfirst($order->status ?? 'pending') }}
                                </span>
                            </td>
                            <td>{{ $order->createdBy->name ?? 'N/A' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No pending orders found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-center mt-3">
            {{ $orders->links() }}
        </div>
    </div>
</div>
@endsection
