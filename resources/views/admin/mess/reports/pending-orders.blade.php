@extends('admin.layouts.master')

@section('setup_content')
@include('admin.mess.reports.partials.report-styles')
<div class="card" >
    <div class="card-header">
        <h5 class="mb-0">
            <iconify-icon icon="solar:cart-check-bold" class="me-2"></iconify-icon>
            Pending Purchase Orders
        </h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        @include('admin.mess.reports.partials.report-sno-th')
                        @include('admin.mess.reports.partials.report-sort-th', ['sortKey' => 'po_number', 'label' => 'PO Number', 'defaultDir' => 'asc'])
                        @include('admin.mess.reports.partials.report-sort-th', ['sortKey' => 'po_date', 'label' => 'PO Date', 'defaultDir' => 'desc', 'defaultSort' => 'po_date'])
                        <th>Vendor</th>
                        <th>Store</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $index => $order)
                        <tr>
                            <td class="text-center text-muted mess-report-sno-cell">@include('admin.mess.reports.partials.report-serial-number', ['paginator' => $orders, 'index' => $index])</td>
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
                            <td colspan="8" class="text-center text-muted py-4">No pending orders found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>
@endsection
