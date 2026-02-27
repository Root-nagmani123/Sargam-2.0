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
                    <p><strong>Payment Mode:</strong> {{ $purchaseOrder->payment_code ?? 'N/A' }}</p>
                    <p><strong>Contact Number:</strong> {{ $purchaseOrder->contact_number ?? 'N/A' }}</p>
                    @if($purchaseOrder->delivery_address)
                        <p><strong>Delivery Address:</strong> {{ $purchaseOrder->delivery_address }}</p>
                    @endif
                </div>
                <div class="col-md-6">
                    <p><strong>Status:</strong>
                        <span class="badge bg-{{ $purchaseOrder->status == 'approved' ? 'success' : ($purchaseOrder->status == 'rejected' ? 'danger' : ($purchaseOrder->status == 'completed' ? 'primary' : 'warning')) }}">
                            {{ ucfirst($purchaseOrder->status) }}
                        </span>
                    </p>
                    <p><strong>Grand Total:</strong> ₹{{ number_format($purchaseOrder->total_amount, 2) }}</p>
                    <p><strong>Created By:</strong> {{ $purchaseOrder->creator->name ?? 'N/A' }}</p>
                    @if($purchaseOrder->approved_by)
                        <p><strong>Approved By:</strong> {{ $purchaseOrder->approver->name ?? 'N/A' }}</p>
                        <p><strong>Approved At:</strong> {{ $purchaseOrder->approved_at->format('d/m/Y H:i') }}</p>
                    @endif
                    <p><strong>Remarks:</strong> {{ $purchaseOrder->remarks ?? 'N/A' }}</p>
                    <p><strong>Bill:</strong>
                        @if($purchaseOrder->bill_path)
                            <a href="{{ asset('storage/' . $purchaseOrder->bill_path) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">View / Download Bill</a>
                        @else
                            <span class="text-muted">No bill uploaded</span>
                        @endif
                    </p>
                </div>
            </div>

            <h5 class="mt-3">Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Item Name</th>
                            <th>Item Code</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Price</th>
                            <th>Tax (%)</th>
                            <th>Total Amount</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($purchaseOrder->items as $item)
                            <tr>
                                <td>{{ optional($item->itemSubcategory)->item_name ?? optional($item->inventory)->item_name ?? 'N/A' }}</td>
                                <td>{{ optional($item->itemSubcategory)->item_code ?? '-' }}</td>
                                <td>{{ $item->quantity }}</td>
                                <td>{{ $item->unit ?? '-' }}</td>
                                <td>₹{{ number_format($item->unit_price, 2) }}</td>
                                <td>{{ number_format((float) ($item->tax_percent ?? 0), 2) }}%</td>
                                <td>₹{{ number_format($item->total_price, 2) }}</td>
                                <td>{{ $item->description ?? '-' }}</td>
                            </tr>
                        @endforeach
                        <tr class="table-light fw-bold">
                            <td colspan="6" class="text-end">Grand Total:</td>
                            <td colspan="2">₹{{ number_format($purchaseOrder->total_amount, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <a href="{{ route('admin.mess.purchaseorders.index') }}" class="btn btn-secondary">Back</a>
        </div>
    </div>
</div>
@endsection
