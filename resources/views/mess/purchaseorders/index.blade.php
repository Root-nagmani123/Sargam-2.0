@extends('admin.layouts.master')
@section('title', 'Purchase Orders')
@section('setup_content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Purchase Orders</h4>
        <a href="{{ route('mess.purchaseorders.create') }}" class="btn btn-primary">Create Purchase Order</a>
    </div>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>PO Number</th>
                <th>Date</th>
                <th>Vendor</th>
                <th>Store</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        @foreach($purchaseOrders as $po)
            <tr>
                <td>{{ $po->po_number }}</td>
                <td>{{ $po->po_date->format('d/m/Y') }}</td>
                <td>{{ $po->vendor->name ?? 'N/A' }}</td>
                <td>{{ $po->store->store_name ?? 'N/A' }}</td>
                <td>₹{{ number_format($po->total_amount, 2) }}</td>
                <td>
                    <span class="badge bg-{{ $po->status == 'approved' ? 'success' : ($po->status == 'rejected' ? 'danger' : ($po->status == 'completed' ? 'primary' : 'warning')) }}">
                        {{ ucfirst($po->status) }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('mess.purchaseorders.show', $po->id) }}" class="btn btn-sm btn-info">View</a>
                    @if($po->status == 'pending')
                        <form action="{{ route('mess.purchaseorders.approve', $po->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button class="btn btn-sm btn-success">Approve</button>
                        </form>
                        <form action="{{ route('mess.purchaseorders.reject', $po->id) }}" method="POST" style="display:inline;">
                            @csrf
                            <button class="btn btn-sm btn-danger">Reject</button>
                        </form>
                    @endif
                    @if($po->status == 'approved')
                        <a href="{{ route('mess.inboundtransactions.create', ['purchase_order_id' => $po->id]) }}" class="btn btn-sm btn-primary">Receive Goods</a>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
