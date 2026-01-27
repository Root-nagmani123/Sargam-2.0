@extends('admin.layouts.master')
@section('title', 'Purchase Order Details')
@section('setup_content')
<div class="container-fluid">
    <h4>Purchase Order Details</h4>
    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>PO Number:</strong> {{ $purchaseOrder->po_number }}</p>
                    <p><strong>PO Date:</strong> {{ $purchaseOrder->po_date->format('d/m/Y') }}</p>
                    <p><strong>Delivery Date:</strong> {{ $purchaseOrder->delivery_date ? $purchaseOrder->delivery_date->format('d/m/Y') : 'N/A' }}</p>
                    <p><strong>Vendor:</strong> {{ $purchaseOrder->vendor->name ?? 'N/A' }}</p>
                    <p><strong>Store:</strong> {{ $purchaseOrder->store->store_name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong> 
                        <span class="badge bg-{{ $purchaseOrder->status == 'approved' ? 'success' : ($purchaseOrder->status == 'rejected' ? 'danger' : ($purchaseOrder->status == 'completed' ? 'primary' : 'warning')) }}">
                            {{ ucfirst($purchaseOrder->status) }}
                        </span>
                    </p>
                    <p><strong>Total Amount:</strong> ₹{{ number_format($purchaseOrder->total_amount, 2) }}</p>
                    <p><strong>Created By:</strong> {{ $purchaseOrder->creator->name ?? 'N/A' }}</p>
                    @if($purchaseOrder->approved_by)
                        <p><strong>Approved By:</strong> {{ $purchaseOrder->approver->name ?? 'N/A' }}</p>
                        <p><strong>Approved At:</strong> {{ $purchaseOrder->approved_at->format('d/m/Y H:i') }}</p>
                    @endif
                    <p><strong>Remarks:</strong> {{ $purchaseOrder->remarks ?? 'N/A' }}</p>
                </div>
            </div>
            
            <h5 class="mt-3">Items</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Unit Price</th>
                        <th>Total Price</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseOrder->items as $item)
                        <tr>
                            <td>{{ $item->inventory->item_name ?? 'N/A' }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>{{ $item->unit ?? '-' }}</td>
                            <td>₹{{ number_format($item->unit_price, 2) }}</td>
                            <td>₹{{ number_format($item->total_price, 2) }}</td>
                            <td>{{ $item->description ?? '-' }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" class="text-end"><strong>Total:</strong></td>
                        <td colspan="2"><strong>₹{{ number_format($purchaseOrder->total_amount, 2) }}</strong></td>
                    </tr>
                </tbody>
            </table>
            
            <a href="{{ route('admin.mess.purchaseorders.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>
@endsection
